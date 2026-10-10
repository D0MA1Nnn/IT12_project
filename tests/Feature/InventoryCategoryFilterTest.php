<?php

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['services.online_backup.path' => null]);
});

/** @return array{owner: User, hardware: Category, materials: Category, inventories: array<string, Inventory>} */
function inventoryCategoryFilterFixtures(): array
{
    $owner = User::forceCreate([
        'username' => 'inventory-filter-owner', 'password_hash' => Hash::make('secret'),
        'role' => 'OWNER', 'is_active' => true,
    ]);
    $hardware = Category::create(['category_name' => 'Hardware', 'is_active' => true]);
    $materials = Category::create(['category_name' => 'Construction Materials', 'is_active' => true]);
    Category::create(['category_name' => 'Archived Category', 'is_active' => false]);
    $inventories = [];

    foreach ([
        'in_stock' => [$hardware, 'Common Nail', 10],
        'low_stock' => [$hardware, 'Concrete Nail', 2],
        'out_of_stock' => [$hardware, 'Claw Hammer', 0],
        'other' => [$materials, 'Portland Cement', 2],
    ] as $key => [$category, $name, $quantity]) {
        $product = Product::create([
            'category_id' => $category->category_id, 'product_name' => $name, 'is_active' => true,
        ]);
        $inventories[$key] = Inventory::create([
            'product_id' => $product->product_id, 'quantity_on_hand' => $quantity, 'reorder_level' => 5,
        ]);
    }

    return compact('owner', 'hardware', 'materials', 'inventories');
}

test('inventory category selection excludes products from other categories', function () {
    $fixtures = inventoryCategoryFilterFixtures();
    $expectedIds = array_map(fn (Inventory $inventory): int => $inventory->inventory_id, array_slice($fixtures['inventories'], 0, 3));

    $this->actingAs($fixtures['owner'])->get(route('inventory.index', ['category' => $fixtures['hardware']->category_id]))
        ->assertViewHas('inventories', fn ($inventories): bool => $inventories->pluck('inventory_id')->all() === array_values($expectedIds))
        ->assertSee('Common Nail')->assertSee('Concrete Nail')->assertSee('Claw Hammer')
        ->assertDontSee('Portland Cement');

    $this->assertDatabaseCount('inventories', 4);
});

test('all categories returns inventory from every category', function (array $filters) {
    $fixtures = inventoryCategoryFilterFixtures();

    $this->actingAs($fixtures['owner'])->get(route('inventory.index', $filters))
        ->assertViewHas('inventories', fn ($inventories): bool => $inventories->count() === 4)
        ->assertSee('Common Nail')->assertSee('Portland Cement');
})->with(['default' => [[]], 'all categories selected' => [['category' => '']]]);

test('inventory categories combine with each stock filter', function (string $stock) {
    $fixtures = inventoryCategoryFilterFixtures();
    $matchingInventory = $fixtures['inventories'][$stock];

    $this->actingAs($fixtures['owner'])->get(route('inventory.index', [
        'category' => $fixtures['hardware']->category_id, 'stock' => $stock,
    ]))->assertViewHas('inventories', fn ($inventories): bool => $inventories->count() === 1 && $inventories->first()->is($matchingInventory));
})->with(['in stock' => 'in_stock', 'low stock' => 'low_stock', 'out of stock' => 'out_of_stock']);

test('inventory category search and stock filters work together', function () {
    $fixtures = inventoryCategoryFilterFixtures();
    $matchingInventory = $fixtures['inventories']['low_stock'];

    $this->actingAs($fixtures['owner'])->get(route('inventory.index', [
        'category' => $fixtures['hardware']->category_id, 'search' => 'Nail', 'stock' => 'low_stock',
    ]))->assertViewHas('inventories', fn ($inventories): bool => $inventories->count() === 1 && $inventories->first()->is($matchingInventory))
        ->assertSee('Concrete Nail')->assertDontSee('Common Nail')->assertDontSee('Portland Cement');
});

test('the category dropdown keeps its selection applies automatically and can be cleared', function () {
    $fixtures = inventoryCategoryFilterFixtures();

    $response = $this->actingAs($fixtures['owner'])->get(route('inventory.index', ['category' => $fixtures['hardware']->category_id]));

    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    $select = $xpath->query('//form[@data-auto-filter]//select[@name="category"]')->item(0);
    expect($select)->not->toBeNull();
    $options = $xpath->query('option', $select);
    expect($options->length)->toBe(3);
    expect(trim($options->item(0)->textContent))->toBe('All Categories');
    expect($options->item(0)->getAttribute('value'))->toBe('');
    $selected = $xpath->query('option[@selected]', $select)->item(0);
    expect($selected->getAttribute('value'))->toBe((string) $fixtures['hardware']->category_id);
    $clearLink = $xpath->query('//form[@data-auto-filter]//a[normalize-space(.)="Clear"]')->item(0);
    expect($clearLink->getAttribute('href'))->toBe(route('inventory.index'));
    expect($xpath->query('//form[@data-auto-filter]//button')->length)->toBe(0);
});

test('a category with no matching inventory shows the empty state', function () {
    $fixtures = inventoryCategoryFilterFixtures();
    $emptyCategory = Category::create(['category_name' => 'Tools', 'is_active' => true]);

    $response = $this->actingAs($fixtures['owner'])->get(route('inventory.index', ['category' => $emptyCategory->category_id]))
        ->assertViewHas('inventories', fn ($inventories): bool => $inventories->isEmpty())
        ->assertSee('No inventory records found.');

    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    $emptyCell = $xpath->query('//table[contains(@class,"inventory-table")]/tbody/tr/td')->item(0);
    expect($emptyCell->getAttribute('colspan'))->toBe('6');
});

test('inventory shows stock details without an action column or product editing controls', function () {
    $fixtures = inventoryCategoryFilterFixtures();

    $response = $this->actingAs($fixtures['owner'])->get(route('inventory.index'))
        ->assertSee('Common Nail')->assertSee('Portland Cement')
        ->assertDontSee('Edit Product')->assertDontSee('inventoryEditModal', false)
        ->assertDontSee('edit-inventory-product', false);

    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    $headers = $xpath->query('//table[contains(@class,"inventory-table")]/thead/tr/th');
    $labels = [];
    foreach ($headers as $header) {
        $labels[] = trim($header->textContent);
    }
    expect($labels)->toBe(['Product', 'Category', 'Available Qty', 'Unit', 'Reorder Level', 'Status']);
    $rows = $xpath->query('//table[contains(@class,"inventory-table")]/tbody/tr');
    expect($rows->length)->toBe(4);
    foreach ($rows as $row) {
        expect($xpath->query('td', $row)->length)->toBe(6);
    }
    expect($xpath->query('//table[contains(@class,"inventory-table")]//button')->length)->toBe(0);
    $this->assertDatabaseCount('inventories', 4);
    $this->assertDatabaseCount('activity_logs', 0);
});

test('category names are safely escaped in the inventory dropdown', function () {
    $fixtures = inventoryCategoryFilterFixtures();
    $name = '<script>alert("category")</script>';
    $fixtures['hardware']->update(['category_name' => $name]);

    $this->actingAs($fixtures['owner'])->get(route('inventory.index'))
        ->assertSee($name)->assertDontSee($name, false);
});

test('inventory filtering requires authentication', function () {
    $this->get(route('inventory.index', ['category' => 1]))->assertRedirect(route('login'));
});

test('staff cannot access owner inventory filters', function () {
    $staff = User::forceCreate([
        'username' => 'inventory-filter-staff', 'password_hash' => Hash::make('secret'),
        'role' => 'SALES_CLERK', 'is_active' => true,
    ]);

    $this->actingAs($staff)->get(route('inventory.index', ['category' => 1]))->assertForbidden();
});
