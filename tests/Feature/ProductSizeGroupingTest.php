<?php

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['services.online_backup.path' => null]);
});

/** @return array{owner: User, category: Category, supplier: Supplier, unit: UnitOfMeasure} */
function createProductSizeFixtures(): array
{
    $owner = User::forceCreate(['username' => 'size-owner', 'password_hash' => Hash::make('secret'), 'role' => 'OWNER', 'is_active' => true]);
    $category = Category::create(['category_name' => 'Lumber', 'is_active' => true]);
    $supplier = Supplier::create(['supplier_name' => 'Size Supplier', 'is_active' => true]);
    $unit = UnitOfMeasure::create(['unit_name' => 'Piece', 'unit_symbol' => 'pc', 'unit_type' => 'Count', 'is_active' => true]);

    return compact('owner', 'category', 'supplier', 'unit');
}

/** @param array{category: Category, supplier: Supplier, unit: UnitOfMeasure} $fixtures */
function createSizeProduct(array $fixtures, string $name, ?string $size = null, float $price = 150, float $stock = 10): Product
{
    $product = Product::create([
        'category_id' => $fixtures['category']->category_id, 'product_name' => $size ? $size.' '.$name : $name,
        'group_name' => $size ? $name : null, 'size_name' => $size, 'is_active' => true,
    ]);
    Inventory::create(['product_id' => $product->product_id, 'quantity_on_hand' => $stock, 'reorder_level' => 1]);
    $product->productUnits()->create(['unit_id' => $fixtures['unit']->unit_id, 'selling_price' => $price, 'purchase_cost' => 100, 'conversion_factor' => 1, 'is_base_unit' => true, 'is_active' => true]);
    $product->suppliers()->attach($fixtures['supplier']);

    return $product;
}

/** @param array{category: Category, supplier: Supplier, unit: UnitOfMeasure} $fixtures
 * @return array<string, mixed>
 */
function productSizePayload(array $fixtures, array $overrides = []): array
{
    return array_replace([
        'product_name' => 'Lumber', 'category_id' => $fixtures['category']->category_id,
        'supplier_id' => $fixtures['supplier']->supplier_id, 'unit_id' => $fixtures['unit']->unit_id,
        'selling_price' => 150, 'purchase_cost' => 100, 'reorder_level' => 1,
    ], $overrides);
}

test('products can be created with an optional size or without sizes', function (array $options, ?string $group, ?string $size, string $name) {
    $fixtures = createProductSizeFixtures();

    $this->actingAs($fixtures['owner'])->post(route('products.store'), productSizePayload($fixtures, $options))
        ->assertRedirect(route('products.index'))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('products', ['product_name' => $name, 'group_name' => $group, 'size_name' => $size]);
    $this->assertDatabaseHas('inventories', ['quantity_on_hand' => 0]);
    $this->assertDatabaseHas('product_units', ['is_base_unit' => true, 'conversion_factor' => 1, 'selling_price' => 150]);
    $this->assertDatabaseHas('activity_logs', ['module' => 'PRODUCT', 'action' => 'CREATE']);
})->with([
    'with size' => [['has_sizes' => 1, 'size_name' => '2x4'], 'Lumber', '2x4', 'Lumber — 2x4'],
    'no sizes' => [[], null, null, 'Lumber'],
    'unchecked ignores stale size' => [['has_sizes' => 0, 'size_name' => '2x4'], null, null, 'Lumber'],
]);

test('a checked size option requires a nonempty size', function (string $size) {
    $fixtures = createProductSizeFixtures();

    $this->actingAs($fixtures['owner'])->post(route('products.store'), productSizePayload($fixtures, ['has_sizes' => 1, 'size_name' => $size]))
        ->assertSessionHasErrors(['size_name' => 'Enter a size, or turn off Has sizes.']);

    $this->assertDatabaseCount('products', 0);
    $this->assertDatabaseCount('inventories', 0);
})->with(['empty' => [''], 'whitespace' => ['   ']]);

test('duplicate sizes in the same product group are rejected case insensitively', function () {
    $fixtures = createProductSizeFixtures();
    createSizeProduct($fixtures, 'Lumber', '2x4');

    $this->actingAs($fixtures['owner'])->post(route('products.store'), productSizePayload($fixtures, ['product_name' => 'lumber', 'has_sizes' => 1, 'size_name' => '2X4']))
        ->assertSessionHasErrors(['size_name' => 'This size already exists for this product. Edit the existing size instead.']);

    $this->assertDatabaseCount('products', 1);
    $this->assertDatabaseCount('activity_logs', 0);
});

test('a sized product can be edited without changing its identity or stock', function () {
    $fixtures = createProductSizeFixtures();
    $product = createSizeProduct($fixtures, 'Lumber', '2x4', 320, 18);
    $base = $product->productUnits()->firstOrFail();

    $this->actingAs($fixtures['owner'])->put(route('products.update', $product), productSizePayload($fixtures, ['has_sizes' => 1, 'size_name' => '2x4', 'selling_price' => 320]))
        ->assertSessionHasNoErrors()->assertRedirect(route('products.index'));

    $this->assertDatabaseCount('products', 1);
    $this->assertDatabaseHas('products', ['product_id' => $product->product_id, 'group_name' => 'Lumber', 'size_name' => '2x4']);
    $this->assertDatabaseHas('inventories', ['product_id' => $product->product_id, 'quantity_on_hand' => 18]);
    $this->assertDatabaseHas('product_units', ['product_unit_id' => $base->product_unit_id, 'selling_price' => 320]);
});

test('turning off sizes makes a product standalone without merging stock', function () {
    $fixtures = createProductSizeFixtures();
    $product = createSizeProduct($fixtures, 'Lumber', '2x4', 320, 18);
    $other = createSizeProduct($fixtures, 'Lumber', '2x2', 150, 10);

    $this->actingAs($fixtures['owner'])->put(route('products.update', $product), productSizePayload($fixtures, ['has_sizes' => 0, 'product_name' => 'Special Lumber', 'selling_price' => 320]))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('products', ['product_id' => $product->product_id, 'product_name' => 'Special Lumber', 'group_name' => null, 'size_name' => null]);
    $this->assertDatabaseHas('inventories', ['product_id' => $product->product_id, 'quantity_on_hand' => 18]);
    $this->assertDatabaseHas('inventories', ['product_id' => $other->product_id, 'quantity_on_hand' => 10]);
});

test('management groups sizes once and leaves products without sizes standalone', function () {
    $fixtures = createProductSizeFixtures();
    $small = createSizeProduct($fixtures, 'Lumber', '2x2');
    $large = createSizeProduct($fixtures, 'Lumber', '2x4', 320);
    createSizeProduct($fixtures, 'Claw Hammer');

    $response = $this->actingAs($fixtures['owner'])->get(route('products.index'));

    $response->assertViewHas('products', fn ($groups): bool => $groups->total() === 2);
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    expect($xpath->query('//tbody[@data-size-group]')->length)->toBe(2);
    expect($xpath->query('//tr[@data-size-product and not(@hidden)]')->length)->toBe(2);
    expect($xpath->query('//select[@data-size-selector]/option[@value="'.$large->product_id.'"]')->length)->toBe(2);
    expect($xpath->query('//tr[@data-size-product="'.$small->product_id.'"]//button[contains(@class,"add-size-btn")]')->length)->toBe(1);
    $response->assertSee('productHasSizes')->assertSee('productSize')->assertSee('data-product-sizes-script');
});

test('group pagination counts groups and never splits their sizes across pages', function () {
    $fixtures = createProductSizeFixtures();
    foreach (range(1, 11) as $index) {
        createSizeProduct($fixtures, 'Product '.$index, 'Small');
        createSizeProduct($fixtures, 'Product '.$index, 'Large');
    }

    $response = $this->actingAs($fixtures['owner'])->get(route('products.index', ['page' => 2]));

    $response->assertViewHas('products', fn ($groups): bool => $groups->total() === 11 && $groups->count() === 1 && $groups->first()->count() === 2);
});

test('searching a size returns the matching product inside its group', function () {
    $fixtures = createProductSizeFixtures();
    createSizeProduct($fixtures, 'Lumber', '2x2');
    $large = createSizeProduct($fixtures, 'Lumber', '2x4');

    $response = $this->actingAs($fixtures['owner'])->get(route('products.index', ['search' => '2x4']));

    $response->assertViewHas('products', fn ($groups): bool => $groups->total() === 1 && $groups->first()->count() === 1 && $groups->first()->first()->product_id === $large->product_id);
});

test('same named size groups in different categories keep separate inventories', function () {
    $fixtures = createProductSizeFixtures();
    createSizeProduct($fixtures, 'Lumber', '2x2');
    $fixtures['category'] = Category::create(['category_name' => 'Other', 'is_active' => true]);
    createSizeProduct($fixtures, 'Lumber', '2x2');

    $this->actingAs($fixtures['owner'])->get(route('products.index'))
        ->assertViewHas('products', fn ($groups): bool => $groups->total() === 2);
});

test('cashiering displays the size selector below the stock badge', function () {
    $fixtures = createProductSizeFixtures();
    $product = createSizeProduct($fixtures, 'Lumber', '2x2');

    $response = $this->actingAs($fixtures['owner'])->get(route('sales.create'));

    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    expect($xpath->query('//div[@data-size-product="'.$product->product_id.'"]//span[@data-stock-badge]/following-sibling::label[contains(@class,"cashier-size-label")]')->length)->toBe(1);
});

test('cashiering groups sizes but purchasing different sizes deducts each exact stock and prints their names', function () {
    $fixtures = createProductSizeFixtures();
    $small = createSizeProduct($fixtures, 'Lumber', '2x2', 150, 10);
    $large = createSizeProduct($fixtures, 'Lumber', '2x4', 320, 20);
    $this->actingAs($fixtures['owner'])->get(route('sales.create'))
        ->assertViewHas('productGroups', fn ($groups): bool => $groups->count() === 1 && $groups->first()->count() === 2);

    $this->post(route('sales.store'), ['items' => [
        ['product_unit_id' => $small->productUnits()->first()->product_unit_id, 'quantity' => 2],
        ['product_unit_id' => $large->productUnits()->first()->product_unit_id, 'quantity' => 3],
    ], 'payment' => 1260])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('inventories', ['product_id' => $small->product_id, 'quantity_on_hand' => 8]);
    $this->assertDatabaseHas('inventories', ['product_id' => $large->product_id, 'quantity_on_hand' => 17]);
    $this->assertDatabaseCount('sale_items', 2);
    $this->assertDatabaseHas('sales', ['total_amount' => 1260]);
    $this->get(route('sales.create'))->assertSee('2x2 Lumber')->assertSee('2x4 Lumber')
        ->assertViewHas('completedSale', fn (Sale $sale): bool => $sale->items->count() === 2);
});

test('archived sizes stay manageable but are unavailable for new orders', function () {
    $fixtures = createProductSizeFixtures();
    createSizeProduct($fixtures, 'Lumber', '2x2');
    $large = createSizeProduct($fixtures, 'Lumber', '2x4');
    $large->update(['is_active' => false]);

    $this->actingAs($fixtures['owner'])->get(route('products.index'))->assertViewHas('products', fn ($groups): bool => $groups->first()->count() === 2);
    $this->get(route('sales.create'))->assertViewHas('productGroups', fn ($groups): bool => $groups->first()->count() === 1);
});

test('staff cannot add sized products', function () {
    $fixtures = createProductSizeFixtures();
    $staff = User::forceCreate(['username' => 'size-staff', 'password_hash' => Hash::make('secret'), 'role' => 'SALES_CLERK', 'is_active' => true]);

    $this->actingAs($staff)->post(route('products.store'), productSizePayload($fixtures, ['has_sizes' => 1, 'size_name' => '2x4']))->assertForbidden();

    $this->assertDatabaseCount('products', 0);
});

test('size labels are escaped in product and cashier selectors', function () {
    $fixtures = createProductSizeFixtures();
    createSizeProduct($fixtures, 'Lumber', '<script>alert(1)</script>');

    foreach (['products.index', 'sales.create'] as $route) {
        $this->actingAs($fixtures['owner'])->get(route($route))->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }
});

test('legacy size backfill preserves product names stock IDs and transaction history', function () {
    $fixtures = createProductSizeFixtures();
    $small = createSizeProduct($fixtures, '2x2 Lumber', null, 150, 18);
    $large = createSizeProduct($fixtures, '2x4 Lumber', null, 320, 25);
    $nail = createSizeProduct($fixtures, 'Common Nail 2"');
    $pipe = createSizeProduct($fixtures, 'PVC Pipe 1/2"');
    $hammer = createSizeProduct($fixtures, 'Claw Hammer');
    $sale = Sale::create(['user_id' => $fixtures['owner']->user_id, 'sale_date' => now(), 'total_amount' => 150, 'status' => 'COMPLETED', 'delivery_required' => false]);
    $item = SaleItem::create(['sale_id' => $sale->sale_id, 'product_unit_id' => $small->productUnits()->first()->product_unit_id, 'quantity' => 1, 'unit_price' => 150, 'subtotal' => 150]);

    $migration = require database_path('migrations/2026_10_10_062746_add_size_grouping_to_products_table.php');
    $migration->down();
    $migration->up();

    $this->assertDatabaseHas('products', ['product_id' => $small->product_id, 'product_name' => '2x2 Lumber', 'group_name' => 'Lumber', 'size_name' => '2x2']);
    $this->assertDatabaseHas('products', ['product_id' => $large->product_id, 'product_name' => '2x4 Lumber', 'group_name' => 'Lumber', 'size_name' => '2x4']);
    $this->assertDatabaseHas('products', ['product_id' => $nail->product_id, 'group_name' => 'Common Nail', 'size_name' => '2"']);
    $this->assertDatabaseHas('products', ['product_id' => $pipe->product_id, 'group_name' => 'PVC Pipe', 'size_name' => '1/2"']);
    $this->assertDatabaseHas('products', ['product_id' => $hammer->product_id, 'group_name' => null, 'size_name' => null]);
    $this->assertDatabaseHas('inventories', ['product_id' => $small->product_id, 'quantity_on_hand' => 18]);
    $this->assertDatabaseHas('inventories', ['product_id' => $large->product_id, 'quantity_on_hand' => 25]);
    $this->assertDatabaseHas('sale_items', ['sale_item_id' => $item->sale_item_id, 'product_unit_id' => $item->product_unit_id, 'quantity' => 1]);
    $this->assertDatabaseCount('products', 5);
});

test('name and size together must fit the stored product name', function () {
    $fixtures = createProductSizeFixtures();

    $this->actingAs($fixtures['owner'])->post(route('products.store'), productSizePayload($fixtures, ['product_name' => str_repeat('L', 149), 'has_sizes' => 1, 'size_name' => '2x4']))
        ->assertSessionHasErrors(['product_name' => 'The product name and size together must not exceed 150 characters.']);

    $this->assertDatabaseCount('products', 0);
});
