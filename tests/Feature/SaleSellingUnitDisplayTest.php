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

/** @return array{owner: User, product: Product, base: ProductUnit, bag: ProductUnit, box: ProductUnit} */
function createSellingDisplayFixtures(): array
{
    $owner = User::forceCreate(['username' => 'owner', 'password_hash' => Hash::make('secret'), 'role' => 'OWNER', 'is_active' => true]);
    $category = Category::create(['category_name' => 'Materials', 'is_active' => true]);
    $kilogram = UnitOfMeasure::create(['unit_name' => 'Kilogram', 'unit_symbol' => 'kg', 'unit_type' => 'Weight', 'is_active' => true]);
    $bagUnit = UnitOfMeasure::create(['unit_name' => 'Bag', 'unit_symbol' => 'bag', 'unit_type' => 'Count', 'is_active' => true]);
    $boxUnit = UnitOfMeasure::create(['unit_name' => 'Box', 'unit_symbol' => 'box', 'unit_type' => 'Count', 'is_active' => true]);
    $product = Product::create(['category_id' => $category->category_id, 'product_name' => 'Portland Cement', 'is_active' => true]);
    Inventory::create(['product_id' => $product->product_id, 'quantity_on_hand' => 100, 'reorder_level' => 5]);
    $base = ProductUnit::create([
        'product_id' => $product->product_id, 'unit_id' => $kilogram->unit_id, 'selling_price' => 8,
        'purchase_cost' => 7, 'conversion_factor' => 1, 'is_base_unit' => true, 'is_active' => true,
    ]);
    $bag = ProductUnit::create([
        'product_id' => $product->product_id, 'unit_id' => $bagUnit->unit_id, 'selling_price' => 320,
        'purchase_cost' => 280, 'conversion_factor' => 40, 'is_base_unit' => false, 'is_active' => true,
    ]);
    $box = ProductUnit::create([
        'product_id' => $product->product_id, 'unit_id' => $boxUnit->unit_id, 'selling_price' => 80,
        'purchase_cost' => 70, 'conversion_factor' => 10, 'is_base_unit' => false, 'is_active' => true,
    ]);

    return compact('owner', 'product', 'base', 'bag', 'box');
}

test('one invoice line preserves matching selling units and combines mixed units in kilograms', function (array $selections, float $displayQuantity, string $displayUnit, float $baseQuantity, float $total) {
    $fixtures = createSellingDisplayFixtures();
    $items = array_map(fn (array $selection): array => [
        'product_unit_id' => $fixtures[$selection[0]]->product_unit_id, 'quantity' => $selection[1],
    ], $selections);

    $this->actingAs($fixtures['owner'])->post(route('sales.store'), ['items' => $items, 'payment' => 1000])->assertSessionHasNoErrors();

    $this->assertDatabaseCount('sale_items', 1);
    $item = SaleItem::firstOrFail();
    expect($item->sellingQuantity())->toBe($displayQuantity);
    expect($item->sellingUnitName())->toBe($displayUnit);
    expect($item->baseStockQuantity())->toBe($baseQuantity);
    $this->assertDatabaseHas('sale_items', ['product_unit_id' => $fixtures['base']->product_unit_id, 'quantity' => $baseQuantity, 'subtotal' => $total]);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 100 - $baseQuantity]);
    $this->get(route('sales.create'))->assertSee('₱'.number_format($total, 2))
        ->assertViewHas('completedSale', fn (Sale $receipt): bool => $receipt->items->count() === 1
            && $receipt->items->first()->sellingQuantity() === $displayQuantity
            && $receipt->items->first()->sellingUnitName() === $displayUnit);
})->with([
    'one bag' => [[['bag', 1]], 1.0, 'Bag', 40.0, 320.0],
    'two bags' => [[['bag', 2]], 2.0, 'Bag', 80.0, 640.0],
    'repeated bags' => [[['bag', 1], ['bag', 0.5]], 1.5, 'Bag', 60.0, 480.0],
    'bag then kilo' => [[['bag', 1], ['base', 1]], 41.0, 'Kilogram', 41.0, 328.0],
    'kilo then bag' => [[['base', 0.5], ['bag', 1]], 40.5, 'Kilogram', 40.5, 324.0],
    'bag box and bag' => [[['bag', 1], ['box', 1], ['bag', 1]], 90.0, 'Kilogram', 90.0, 720.0],
    'same-factor units' => [[['box', 2]], 2.0, 'Box', 20.0, 160.0],
]);

test('bag receipt preserves selling unit price and quantity after unit settings change', function () {
    $fixtures = createSellingDisplayFixtures();
    $this->actingAs($fixtures['owner'])->post(route('sales.store'), [
        'items' => [['product_unit_id' => $fixtures['bag']->product_unit_id, 'quantity' => 1]], 'payment' => 320,
    ])->assertSessionHasNoErrors();
    $fixtures['bag']->update(['conversion_factor' => 50, 'selling_price' => 400]);
    $fixtures['bag']->unit->update(['unit_name' => 'Sack']);

    $this->get(route('sales.create'))->assertSee('<span>Bag</span>', false)->assertSee('₱320.00')
        ->assertViewHas('completedSale', fn (Sale $receipt): bool => $receipt->items->first()->sellingQuantity() === 1.0
            && $receipt->items->first()->sellingUnitPrice() === 320.0);

    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 60]);
});

test('COD bag delivery returns saved base stock exactly once despite later unit changes', function () {
    $fixtures = createSellingDisplayFixtures();
    $this->actingAs($fixtures['owner'])->post(route('sales.store'), [
        'items' => [['product_unit_id' => $fixtures['bag']->product_unit_id, 'quantity' => 1]],
        'payment_method' => 'COD', 'delivery_required' => 1, 'delivery_fee' => 10,
        'customer_name' => 'Test Customer', 'customer_contact_number' => '09171234567', 'delivery_address' => 'Davao City',
    ])->assertSessionHasNoErrors();
    $sale = Sale::firstOrFail();
    $this->assertDatabaseHas('sales', ['total_amount' => 330, 'payment_status' => 'UNPAID']);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 60]);
    $fixtures['base']->update(['conversion_factor' => 2]);

    $this->patch(route('sales.cancel-delivery', $sale), ['delivery_cancel_reason' => 'Customer cancelled'])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 100]);
    $this->patch(route('sales.cancel-delivery', $sale), ['delivery_cancel_reason' => 'Duplicate'])->assertSessionHas('error');
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 100]);
});

test('selling display metadata cannot be supplied by checkout clients', function () {
    $fixtures = createSellingDisplayFixtures();

    $this->actingAs($fixtures['owner'])->post(route('sales.store'), [
        'items' => [['product_unit_id' => $fixtures['bag']->product_unit_id, 'quantity' => 1,
            'selling_details' => ['quantity' => 1, 'unit' => 'Bag', 'base_quantity' => 0.001, 'unit_price' => 1]]],
        'payment' => 1,
    ])->assertSessionHasErrors('items.0');

    $this->assertDatabaseCount('sales', 0);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 100]);
});

test('receipt escapes saved selling unit labels', function () {
    $fixtures = createSellingDisplayFixtures();
    $label = '<script>alert(1)</script>';
    $fixtures['bag']->unit->update(['unit_name' => $label]);
    $this->actingAs($fixtures['owner'])->post(route('sales.store'), [
        'items' => [['product_unit_id' => $fixtures['bag']->product_unit_id, 'quantity' => 1]], 'payment' => 320,
    ])->assertSessionHasNoErrors();

    $this->get(route('sales.create'))->assertSee(e($label), false)->assertDontSee($label, false);
});
