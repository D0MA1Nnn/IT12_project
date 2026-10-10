<?php

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductUnit;
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

/** @return array{owner: User, product: Product, base: ProductUnit, piece: UnitOfMeasure} */
function conversionPrecisionFixtures(): array
{
    $owner = User::forceCreate(['username' => 'precision-owner', 'password_hash' => Hash::make('secret'), 'role' => 'OWNER', 'is_active' => true]);
    $category = Category::create(['category_name' => 'Hardware', 'is_active' => true]);
    $kilogram = UnitOfMeasure::create(['unit_name' => 'Kilogram', 'unit_symbol' => 'kg', 'unit_type' => 'Weight', 'is_active' => true]);
    $piece = UnitOfMeasure::create(['unit_name' => 'Piece', 'unit_symbol' => 'pc', 'unit_type' => 'Count', 'is_active' => true]);
    $product = Product::create(['category_id' => $category->category_id, 'product_name' => 'Precision Nails', 'is_active' => true]);
    Inventory::create(['product_id' => $product->product_id, 'quantity_on_hand' => 1, 'reorder_level' => 0]);
    $base = $product->productUnits()->create([
        'unit_id' => $kilogram->unit_id, 'selling_price' => 165, 'purchase_cost' => 150,
        'conversion_factor' => 1, 'is_base_unit' => true, 'is_active' => true,
    ]);

    return compact('owner', 'product', 'base', 'piece');
}

dataset('conversion precision actions', ['add' => ['post'], 'edit' => ['put']]);

test('adding and editing unit conversions preserve up to eight decimal places', function (string $method, string $factor, string $stored, string $sellingPrice, string $purchaseCost) {
    $fixtures = conversionPrecisionFixtures();
    $payload = ['unit_id' => $fixtures['piece']->unit_id, 'conversion_factor' => $factor];
    $endpoint = route('products.units.store', $fixtures['product']);
    if ($method === 'put') {
        $unit = $fixtures['product']->productUnits()->create([
            'unit_id' => $fixtures['piece']->unit_id, 'conversion_factor' => 0.01,
            'selling_price' => 1.65, 'purchase_cost' => 1.5, 'is_base_unit' => false, 'is_active' => true,
        ]);
        $endpoint = route('products.units.update', [$fixtures['product'], $unit]);
    }

    $this->actingAs($fixtures['owner'])->from(route('products.index'))->{$method}($endpoint, $payload)
        ->assertSessionHasNoErrors()->assertRedirect(route('products.index'));

    $saved = $fixtures['product']->productUnits()->where('unit_id', $fixtures['piece']->unit_id)->firstOrFail();
    expect($saved->conversion_factor)->toBe($stored);
    expect($saved->selling_price)->toBe($sellingPrice);
    expect($saved->purchase_cost)->toBe($purchaseCost);
    $this->assertDatabaseCount('product_units', 2);
    $this->assertDatabaseCount('activity_logs', 1);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 1]);
})->with('conversion precision actions')->with([
    'screenshot value' => ['0.00605', '0.00605000', '1.00', '0.91'],
    'eight decimal places' => ['0.00605001', '0.00605001', '1.00', '0.91'],
    'smallest positive conversion' => ['0.00000001', '0.00000001', '0.00', '0.00'],
    'whole conversion' => ['40', '40.00000000', '6600.00', '6000.00'],
]);

test('conversions beyond eight decimal places cannot change a product unit or stock', function (string $method, string $factor) {
    $fixtures = conversionPrecisionFixtures();
    $unit = $fixtures['product']->productUnits()->create([
        'unit_id' => $fixtures['piece']->unit_id, 'conversion_factor' => 0.01,
        'selling_price' => 1.65, 'purchase_cost' => 1.5, 'is_base_unit' => false, 'is_active' => true,
    ]);
    $original = $unit->fresh()->getAttributes();
    $endpoint = $method === 'post' ? route('products.units.store', $fixtures['product']) : route('products.units.update', [$fixtures['product'], $unit]);

    $this->actingAs($fixtures['owner'])->{$method}($endpoint, [
        'unit_id' => $fixtures['piece']->unit_id, 'conversion_factor' => $factor,
    ])->assertSessionHasErrors(['conversion_factor' => 'Conversion factor can only have up to 8 decimal places.']);

    $this->assertDatabaseHas('product_units', $original);
    $this->assertDatabaseCount('product_units', 2);
    $this->assertDatabaseCount('activity_logs', 0);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 1]);
})->with('conversion precision actions')->with(['nine digits' => '0.006050001', 'below minimum precision' => '0.000000001']);

test('sales retain eight decimal base quantities while preserving the selected selling quantity', function (float $quantity, string $baseQuantity, string $remaining) {
    $fixtures = conversionPrecisionFixtures();
    $piece = $fixtures['product']->productUnits()->create([
        'unit_id' => $fixtures['piece']->unit_id, 'conversion_factor' => '0.00605001',
        'selling_price' => 1, 'purchase_cost' => 0.91, 'is_base_unit' => false, 'is_active' => true,
    ]);

    $this->actingAs($fixtures['owner'])->post(route('sales.store'), [
        'items' => [['product_unit_id' => $piece->product_unit_id, 'quantity' => $quantity]], 'payment' => 10,
    ])->assertSessionHasNoErrors();

    $item = SaleItem::sole();
    expect($item->quantity)->toBe($baseQuantity);
    expect($item->baseStockQuantity())->toBe((float) $baseQuantity);
    expect($item->sellingQuantity())->toBe($quantity);
    expect($fixtures['product']->inventory->fresh()->quantity_on_hand)->toBe($remaining);
})->with([
    'one piece' => [1.0, '0.00605001', '0.99394999'],
    'half unit' => [0.5, '0.00302501', '0.99697499'],
    'one and a half units' => [1.5, '0.00907502', '0.99092498'],
]);

test('a conversion exceeding stock by one hundred-millionth is rejected', function () {
    $fixtures = conversionPrecisionFixtures();
    $fixtures['product']->inventory->update(['quantity_on_hand' => '0.00605000']);
    $piece = $fixtures['product']->productUnits()->create([
        'unit_id' => $fixtures['piece']->unit_id, 'conversion_factor' => '0.00605001',
        'selling_price' => 1, 'purchase_cost' => 0.91, 'is_base_unit' => false, 'is_active' => true,
    ]);

    $this->actingAs($fixtures['owner'])->post(route('sales.store'), [
        'items' => [['product_unit_id' => $piece->product_unit_id, 'quantity' => 1]], 'payment' => 10,
    ])->assertSessionHasErrors('items');

    $this->assertDatabaseCount('sales', 0);
    $this->assertDatabaseCount('sale_items', 0);
    $this->assertDatabaseCount('activity_logs', 0);
    expect($fixtures['product']->inventory->fresh()->quantity_on_hand)->toBe('0.00605000');
});

test('purchasing units with eight decimal conversions adds their full base quantity', function () {
    $fixtures = conversionPrecisionFixtures();
    $supplier = Supplier::create(['supplier_name' => 'Precision Supplier', 'is_active' => true]);
    $supplier->products()->attach($fixtures['product']);
    $piece = $fixtures['product']->productUnits()->create([
        'unit_id' => $fixtures['piece']->unit_id, 'conversion_factor' => '0.00605001',
        'selling_price' => 1, 'purchase_cost' => 0.91, 'is_base_unit' => false, 'is_active' => true,
    ]);

    $this->actingAs($fixtures['owner'])->post(route('purchases.store'), [
        'supplier_id' => $supplier->supplier_id, 'purchase_date' => '2026-10-10 10:00:00',
        'items' => [['product_unit_id' => $piece->product_unit_id, 'quantity' => 3, 'unit_cost' => 0.91]],
    ])->assertSessionHasNoErrors()->assertRedirect(route('purchases.index'));

    expect($fixtures['product']->inventory->fresh()->quantity_on_hand)->toBe('1.01815003');
    $this->assertDatabaseHas('purchases', ['total_amount' => 2.73, 'status' => 'COMPLETED']);
    $this->assertDatabaseCount('purchase_items', 1);
});

test('cancelled delivery restores the eight decimal stock snapshot even after conversion changes', function () {
    $fixtures = conversionPrecisionFixtures();
    $piece = $fixtures['product']->productUnits()->create([
        'unit_id' => $fixtures['piece']->unit_id, 'conversion_factor' => '0.00605001',
        'selling_price' => 1, 'purchase_cost' => 0.91, 'is_base_unit' => false, 'is_active' => true,
    ]);
    $this->actingAs($fixtures['owner'])->post(route('sales.store'), [
        'items' => [['product_unit_id' => $piece->product_unit_id, 'quantity' => 5]],
        'payment_method' => 'COD', 'delivery_required' => 1, 'delivery_fee' => 0,
        'customer_name' => 'Customer', 'customer_contact_number' => '09123456789', 'delivery_address' => 'Davao City',
    ])->assertSessionHasNoErrors();
    $sale = Sale::sole();
    expect($fixtures['product']->inventory->fresh()->quantity_on_hand)->toBe('0.96974995');
    $piece->update(['conversion_factor' => '0.02000000']);

    $this->patch(route('sales.cancel-delivery', $sale), ['delivery_cancel_reason' => 'Customer cancelled'])->assertSessionHasNoErrors();

    expect($fixtures['product']->inventory->fresh()->quantity_on_hand)->toBe('1.00000000');
    $this->patch(route('sales.cancel-delivery', $sale), ['delivery_cancel_reason' => 'Duplicate'])->assertSessionHas('error');
    expect($fixtures['product']->inventory->fresh()->quantity_on_hand)->toBe('1.00000000');
});

test('the conversion form allows eight decimal quantities on both sides', function () {
    $fixtures = conversionPrecisionFixtures();
    $response = $this->actingAs($fixtures['owner'])->get(route('products.index'));
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);

    foreach (['conversionSelectedQty', 'conversionBaseQty'] as $id) {
        $input = $xpath->query('//input[@id="'.$id.'"]')->item(0);
        expect($input->getAttribute('step'))->toBe('0.00000001');
        expect($input->getAttribute('min'))->toBe('0.00000001');
    }
});
