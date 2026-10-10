<?php

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['services.online_backup.path' => null]);
});

function purchaseDetailsOwner(): User
{
    return User::forceCreate([
        'username' => 'purchase-owner', 'password_hash' => Hash::make('secret'),
        'role' => 'OWNER', 'is_active' => true,
    ]);
}

/** @return array{owner: User, supplier: Supplier, product: Product, bag: ProductUnit, kilogram: ProductUnit, purchase: Purchase} */
function purchaseDetailsFixtures(): array
{
    $owner = purchaseDetailsOwner();
    $supplier = Supplier::create(['supplier_name' => 'Cement Supplier', 'is_active' => true]);
    $category = Category::create(['category_name' => 'Materials', 'is_active' => true]);
    $product = Product::create(['product_name' => 'Portland Cement', 'category_id' => $category->category_id, 'is_active' => true]);
    $bagUnit = UnitOfMeasure::create(['unit_name' => 'Bag', 'unit_symbol' => 'bag', 'unit_type' => 'COUNT', 'is_active' => true]);
    $kgUnit = UnitOfMeasure::create(['unit_name' => 'Kilogram', 'unit_symbol' => 'kg', 'unit_type' => 'WEIGHT', 'is_active' => true]);
    $bag = ProductUnit::create([
        'product_id' => $product->product_id, 'unit_id' => $bagUnit->unit_id,
        'conversion_factor' => 40, 'selling_price' => 1100, 'purchase_cost' => 999,
        'is_base_unit' => false, 'is_active' => true,
    ]);
    $kilogram = ProductUnit::create([
        'product_id' => $product->product_id, 'unit_id' => $kgUnit->unit_id,
        'conversion_factor' => 1, 'selling_price' => 110, 'purchase_cost' => 100,
        'is_base_unit' => true, 'is_active' => true,
    ]);
    Inventory::create(['product_id' => $product->product_id, 'quantity_on_hand' => 100, 'reorder_level' => 5]);
    $purchase = Purchase::create([
        'supplier_id' => $supplier->supplier_id, 'user_id' => $owner->user_id,
        'purchase_date' => '2026-10-10 13:44:00', 'total_amount' => 1000, 'status' => 'COMPLETED',
    ]);
    PurchaseItem::create(['purchase_id' => $purchase->purchase_id, 'product_unit_id' => $bag->product_unit_id, 'quantity' => 2, 'unit_cost' => 300, 'subtotal' => 600]);
    PurchaseItem::create(['purchase_id' => $purchase->purchase_id, 'product_unit_id' => $kilogram->product_unit_id, 'quantity' => 5, 'unit_cost' => 80, 'subtotal' => 400]);

    return compact('owner', 'supplier', 'product', 'bag', 'kilogram', 'purchase');
}

test('purchase View opens read-only details with original purchased units costs and totals', function () {
    $fixtures = purchaseDetailsFixtures();
    $id = $fixtures['purchase']->purchase_id;

    $response = $this->actingAs($fixtures['owner'])->get(route('purchases.index'));

    $response->assertSee('<th>Action</th>', false)
        ->assertSee('data-view-purchase="purchaseDetailsModal'.$id.'"', false)
        ->assertSee('aria-labelledby="purchaseDetailsTitle'.$id.'"', false)
        ->assertSee('Purchase Details')->assertSee('10/10/2026 1:44 PM');
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
    $xpath = new DOMXPath($document);
    $modal = $xpath->query('//*[@id="purchaseDetailsModal'.$id.'"]')->item(0);
    expect($modal->getAttribute('role'))->toBe('dialog');
    expect($modal->getAttribute('aria-hidden'))->toBe('true');
    expect($xpath->query('.//form | .//input | .//select | .//textarea', $modal)->length)->toBe(0);
    $rows = $xpath->query('.//tbody/tr', $modal);
    $values = [];
    foreach ($rows as $row) {
        $values[] = array_map(fn (DOMNode $cell): string => trim($cell->textContent), iterator_to_array($xpath->query('./td', $row)));
    }
    expect($values)->toBe([
        ['Portland Cement', 'Bag', '2', '₱300.00', '₱600.00'],
        ['Portland Cement', 'Kilogram', '5', '₱80.00', '₱400.00'],
    ]);
    expect($modal->textContent)->toContain('Cement Supplier', 'purchase-owner', 'COMPLETED', '₱1,000.00');
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 100]);
    $this->assertDatabaseCount('purchases', 1);
    $this->assertDatabaseCount('purchase_items', 2);
    $this->assertDatabaseCount('activity_logs', 0);
});

test('each purchase View has its own items and total rather than another purchase details', function () {
    $fixtures = purchaseDetailsFixtures();
    $second = Purchase::create([
        'supplier_id' => $fixtures['supplier']->supplier_id, 'user_id' => $fixtures['owner']->user_id,
        'purchase_date' => '2026-10-11 09:00:00', 'total_amount' => 30, 'status' => 'COMPLETED',
    ]);
    PurchaseItem::create(['purchase_id' => $second->purchase_id, 'product_unit_id' => $fixtures['bag']->product_unit_id, 'quantity' => 3, 'unit_cost' => 10, 'subtotal' => 30]);

    $response = $this->actingAs($fixtures['owner'])->get(route('purchases.index'));

    $response->assertSee('data-view-purchase="purchaseDetailsModal'.$second->purchase_id.'"', false);
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
    $xpath = new DOMXPath($document);
    $modal = $xpath->query('//*[@id="purchaseDetailsModal'.$second->purchase_id.'"]')->item(0);
    expect($xpath->query('.//tbody/tr', $modal)->length)->toBe(1);
    expect($modal->textContent)->toContain('₱10.00', '₱30.00')->not->toContain('₱1,000.00', '₱300.00', 'Kilogram');
});

test('historical purchases retain details for archived suppliers products and units', function () {
    $fixtures = purchaseDetailsFixtures();
    $fixtures['supplier']->update(['is_active' => false]);
    $fixtures['product']->update(['is_active' => false]);
    $fixtures['bag']->update(['is_active' => false]);
    $fixtures['bag']->unit->update(['is_active' => false]);

    $response = $this->actingAs($fixtures['owner'])->get(route('purchases.index'));

    $response->assertSee('Cement Supplier')->assertSee('Portland Cement')->assertSee('Bag')->assertSee('₱600.00');
});

test('purchase details escape supplier product and recorder names', function () {
    $fixtures = purchaseDetailsFixtures();
    $names = ['<img src=x onerror=alert(1)>', '<script>alert(2)</script>', '<svg onload=alert(3)>'];
    $fixtures['supplier']->update(['supplier_name' => $names[0]]);
    $fixtures['product']->update(['product_name' => $names[1]]);
    $fixtures['owner']->update(['username' => $names[2]]);

    $response = $this->actingAs($fixtures['owner'])->get(route('purchases.index'));

    foreach ($names as $name) {
        $response->assertSee($name)->assertDontSee($name, false);
    }
});

test('the empty purchases table spans the Action column without a View button', function () {
    $owner = purchaseDetailsOwner();

    $response = $this->actingAs($owner)->get(route('purchases.index'));

    $response->assertSee('<th>Action</th>', false)->assertSee('colspan="8"', false)
        ->assertSee('No purchase records yet.')->assertDontSee('data-view-purchase=', false);
});

test('guests cannot access purchase details', function () {
    $this->get(route('purchases.index'))->assertRedirect(route('login'));
});

test('purchases remove the status filter without hiding records from an old status query', function () {
    $fixtures = purchaseDetailsFixtures();

    $response = $this->actingAs($fixtures['owner'])->get(route('purchases.index', ['status' => 'cancelled']));

    $response->assertDontSee('name="status"', false)->assertDontSee('All Status')
        ->assertDontSee('purchase-status-select', false)->assertDontSee('>Reset</a>', false)
        ->assertSee('name="search"', false)->assertSee('<th>Status</th>', false)
        ->assertSee('COMPLETED')
        ->assertSee('data-view-purchase="purchaseDetailsModal'.$fixtures['purchase']->purchase_id.'"', false)
        ->assertViewHas('purchases', fn ($purchases): bool => $purchases->count() === 1);
});

test('purchase search still filters records and their View buttons without a status dropdown', function (string $search, int $expectedCount) {
    $fixtures = purchaseDetailsFixtures();

    $response = $this->actingAs($fixtures['owner'])->get(route('purchases.index', ['search' => $search]));

    $response->assertViewHas('purchases', fn ($purchases): bool => $purchases->count() === $expectedCount)
        ->assertSee('>Reset</a>', false)->assertDontSee('name="status"', false);
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    expect($xpath->query('//button[@data-view-purchase]')->length)->toBe($expectedCount);
    expect($xpath->query('//*[@data-purchase-details-modal]')->length)->toBe($expectedCount);
})->with([
    'supplier' => ['Cement Supplier', 1],
    'recorder' => ['purchase-owner', 1],
    'no match' => ['Not an existing supplier', 0],
]);

test('sales clerks cannot access owner purchase details', function () {
    $user = purchaseDetailsOwner();
    $user->update(['role' => 'SALES_CLERK']);

    $this->actingAs($user)->get(route('purchases.index'))->assertForbidden();
});
