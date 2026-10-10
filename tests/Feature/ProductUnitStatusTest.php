<?php

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['services.online_backup.path' => null]);
});

/** @return array{owner: User, product: Product, base: ProductUnit, box: ProductUnit} */
function createProductUnitStatusFixtures(): array
{
    $owner = User::forceCreate(['username' => 'unit-owner', 'password_hash' => Hash::make('secret'), 'role' => 'OWNER', 'is_active' => true]);
    $category = Category::create(['category_name' => 'Hardware', 'is_active' => true]);
    $kilogram = UnitOfMeasure::create(['unit_name' => 'Kilogram', 'unit_symbol' => 'kg', 'unit_type' => 'Weight', 'is_active' => true]);
    $boxUnit = UnitOfMeasure::create(['unit_name' => 'Box', 'unit_symbol' => 'box', 'unit_type' => 'Count', 'is_active' => true]);
    $product = Product::create(['category_id' => $category->category_id, 'product_name' => 'Common Nail 2"', 'is_active' => true]);
    Inventory::create(['product_id' => $product->product_id, 'quantity_on_hand' => 100, 'reorder_level' => 5]);
    $base = ProductUnit::create([
        'product_id' => $product->product_id, 'unit_id' => $kilogram->unit_id, 'selling_price' => 180,
        'purchase_cost' => 135, 'conversion_factor' => 1, 'is_base_unit' => true, 'is_active' => true,
    ]);
    $box = ProductUnit::create([
        'product_id' => $product->product_id, 'unit_id' => $boxUnit->unit_id, 'selling_price' => 300,
        'purchase_cost' => 225, 'conversion_factor' => 1.666667, 'is_base_unit' => false, 'is_active' => true,
    ]);

    return compact('owner', 'product', 'base', 'box');
}

test('owner can disable and enable a selling unit without changing its stock or prices', function (bool $initialActive, string $message) {
    $fixtures = createProductUnitStatusFixtures();
    $fixtures['box']->update(['is_active' => $initialActive]);

    $this->actingAs($fixtures['owner'])->from(route('products.index'))
        ->patch(route('products.units.toggle', [$fixtures['product'], $fixtures['box']]))
        ->assertRedirect(route('products.index'))->assertSessionHas('success', $message)
        ->assertSessionHas('manage_product_units', $fixtures['product']->product_id);

    $this->assertDatabaseHas('product_units', [
        'product_unit_id' => $fixtures['box']->product_unit_id, 'is_active' => ! $initialActive,
        'conversion_factor' => 1.666667, 'selling_price' => 300, 'purchase_cost' => 225,
    ]);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 100]);
    $this->assertDatabaseHas('activity_logs', ['user_id' => $fixtures['owner']->user_id, 'module' => 'PRODUCT', 'action' => 'UPDATE', 'reference_id' => $fixtures['box']->product_unit_id]);
})->with([
    'disable' => [true, 'Product unit disabled successfully.'],
    'enable' => [false, 'Product unit enabled successfully.'],
]);

test('the base inventory unit cannot be disabled', function () {
    $fixtures = createProductUnitStatusFixtures();

    $this->actingAs($fixtures['owner'])->patch(route('products.units.toggle', [$fixtures['product'], $fixtures['base']]))
        ->assertSessionHas('error', 'The base inventory unit cannot be disabled.');

    $this->assertDatabaseHas('product_units', ['product_unit_id' => $fixtures['base']->product_unit_id, 'is_active' => true]);
    $this->assertDatabaseCount('activity_logs', 0);
});

test('guests cannot change a selling unit status', function () {
    $fixtures = createProductUnitStatusFixtures();

    $this->patch(route('products.units.toggle', [$fixtures['product'], $fixtures['box']]))->assertRedirect(route('login'));

    $this->assertDatabaseHas('product_units', ['product_unit_id' => $fixtures['box']->product_unit_id, 'is_active' => true]);
});

test('staff cannot change a selling unit status', function () {
    $fixtures = createProductUnitStatusFixtures();
    $staff = User::forceCreate(['username' => 'unit-staff', 'password_hash' => Hash::make('secret'), 'role' => 'SALES_CLERK', 'is_active' => true]);

    $this->actingAs($staff)->patch(route('products.units.toggle', [$fixtures['product'], $fixtures['box']]))->assertForbidden();

    $this->assertDatabaseHas('product_units', ['product_unit_id' => $fixtures['box']->product_unit_id, 'is_active' => true]);
    $this->assertDatabaseCount('activity_logs', 0);
});

test('a selling unit belonging to another product returns 404 without changing it', function () {
    $fixtures = createProductUnitStatusFixtures();
    $other = Product::create(['category_id' => $fixtures['product']->category_id, 'product_name' => 'Other Nails', 'is_active' => true]);

    $this->actingAs($fixtures['owner'])->patch(route('products.units.toggle', [$other, $fixtures['box']]))->assertNotFound();

    $this->assertDatabaseHas('product_units', ['product_unit_id' => $fixtures['box']->product_unit_id, 'is_active' => true]);
    $this->assertDatabaseCount('activity_logs', 0);
});

test('disabled selling units are unavailable to cashiering and stale sale submissions', function () {
    $fixtures = createProductUnitStatusFixtures();
    $this->actingAs($fixtures['owner'])->patch(route('products.units.toggle', [$fixtures['product'], $fixtures['box']]))->assertSessionHas('success');

    $this->get(route('sales.create'))->assertViewHas('products', fn ($products): bool => $products->first()->productUnits->count() === 1
        && $products->first()->productUnits->first()->product_unit_id === $fixtures['base']->product_unit_id);
    $this->post(route('sales.store'), ['items' => [['product_unit_id' => $fixtures['box']->product_unit_id, 'quantity' => 1]], 'payment' => 300])
        ->assertSessionHasErrors('items');

    $this->assertDatabaseCount('sales', 0);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 100]);
});

test('disabling a sold unit preserves its invoice and transaction history', function () {
    $fixtures = createProductUnitStatusFixtures();
    $this->actingAs($fixtures['owner'])->post(route('sales.store'), ['items' => [['product_unit_id' => $fixtures['box']->product_unit_id, 'quantity' => 1]], 'payment' => 300])
        ->assertSessionHasNoErrors();
    $item = SaleItem::firstOrFail();
    $stock = $fixtures['product']->inventory->fresh()->quantity_on_hand;

    $this->patch(route('products.units.toggle', [$fixtures['product'], $fixtures['box']]))->assertSessionHas('success');

    $this->assertModelExists($item);
    $this->assertModelExists($fixtures['box']);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => $stock]);
    $this->withSession(['completed_sale_id' => $item->sale_id])->get(route('sales.create'))->assertViewHas('completedSale', fn (Sale $receipt): bool => $receipt->items->first()->sellingUnitName() === 'Box'
        && $receipt->items->first()->sellingQuantity() === 1.0);
});

test('product unit data supplies status endpoints for the Units window', function () {
    $fixtures = createProductUnitStatusFixtures();

    $response = $this->actingAs($fixtures['owner'])->get(route('products.index'));

    $response->assertSee('unit-toggle-form')->assertSee('Disable')->assertSee('Enable');
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    $button = $xpath->query('//button[contains(@class,"units-btn")]')->item(0);
    $units = json_decode($button->getAttribute('data-units'), true, flags: JSON_THROW_ON_ERROR);

    expect($units[1]['toggle_url'])->toBe(route('products.units.toggle', [$fixtures['product'], $fixtures['box']]));
});
