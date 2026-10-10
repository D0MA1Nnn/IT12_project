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

/** @return array{owner: User, clerk: User, product: Product, base: ProductUnit} */
function createRetiredAlterationFixtures(): array
{
    $owner = User::forceCreate(['username' => 'owner', 'password_hash' => Hash::make('secret'), 'role' => 'OWNER', 'is_active' => true]);
    $clerk = User::forceCreate(['username' => 'clerk', 'password_hash' => Hash::make('secret'), 'role' => 'SALES_CLERK', 'is_active' => true]);
    $category = Category::create(['category_name' => 'Hardware', 'is_active' => true]);
    $kilogram = UnitOfMeasure::create(['unit_name' => 'Kilogram', 'unit_symbol' => 'kg', 'unit_type' => 'Weight', 'is_active' => true]);
    $product = Product::create(['category_id' => $category->category_id, 'product_name' => 'Common Nail', 'is_active' => true]);
    Inventory::create(['product_id' => $product->product_id, 'quantity_on_hand' => 49.98, 'reorder_level' => 5]);
    $base = ProductUnit::create([
        'product_id' => $product->product_id, 'unit_id' => $kilogram->unit_id, 'selling_price' => 95,
        'purchase_cost' => 80, 'conversion_factor' => 1, 'is_base_unit' => true, 'is_active' => true,
    ]);

    return compact('owner', 'clerk', 'product', 'base');
}

/** @param array{owner: User, clerk: User, product: Product, base: ProductUnit} $fixtures */
function createHistoricalAlteredSale(array $fixtures, bool $delivery): Sale
{
    $sale = Sale::create([
        'user_id' => $fixtures['clerk']->user_id, 'sale_date' => now(), 'total_amount' => 10,
        'status' => $delivery ? 'PENDING' : 'COMPLETED', 'delivery_required' => $delivery,
        'delivery_status' => $delivery ? 'PENDING' : 'NOT_REQUIRED', 'delivery_fee' => 0,
        'payment_method' => $delivery ? 'COD' : 'PAY_NOW', 'payment_status' => $delivery ? 'UNPAID' : 'PAID',
        'payment_received' => $delivery ? null : 10,
    ]);
    SaleItem::create([
        'sale_id' => $sale->sale_id, 'product_unit_id' => $fixtures['base']->product_unit_id,
        'quantity' => 0.02, 'unit_price' => 2, 'subtotal' => 10,
        'alteration' => ['quantity' => 5, 'unit' => 'Piece', 'base_quantity' => 0.02],
    ]);

    return $sale;
}

test('checkout no longer renders Alter controls for owners or staff', function (string $cashier) {
    $fixtures = createRetiredAlterationFixtures();

    $this->actingAs($fixtures[$cashier])->get(route('sales.create'))->assertOk()
        ->assertDontSee('data-order-alter', false)->assertDontSee('alterationModal', false)
        ->assertDontSee('alterationForm', false)->assertSee('CONFIRM SALE');
})->with(['owner', 'clerk']);

test('retired approval URL returns 404 and creates no approval or sale', function (?string $cashier) {
    $fixtures = createRetiredAlterationFixtures();
    if ($cashier) {
        $this->actingAs($fixtures[$cashier]);
    }

    $this->postJson('/sales/alterations', ['admin_username' => 'owner', 'password' => 'secret'])->assertNotFound();

    $this->assertDatabaseCount('sales', 0);
    $this->assertDatabaseCount('sale_items', 0);
    expect(session('sale_alterations', []))->toBe([]);
})->with(['owner' => ['owner'], 'clerk' => ['clerk'], 'guest' => [null]]);

test('checkout rejects an old alteration approval without changing stock', function () {
    $fixtures = createRetiredAlterationFixtures();
    $token = str_repeat('a', 64);
    $this->actingAs($fixtures['clerk'])->withSession(['sale_alterations' => [$token => [
        'cashier_id' => $fixtures['clerk']->user_id, 'product_unit_id' => $fixtures['base']->product_unit_id,
        'base_quantity' => 0.02, 'quantity' => 5, 'unit' => 'Piece', 'unit_price' => 2,
        'subtotal' => 10, 'expires_at' => now()->addMinutes(15)->timestamp,
    ]]])->post(route('sales.store'), [
        'items' => [['product_unit_id' => $fixtures['base']->product_unit_id, 'quantity' => 0.02, 'alteration_token' => $token]],
        'payment' => 10,
    ])->assertSessionHasErrors('items.0');

    $this->assertDatabaseCount('sales', 0);
    $this->assertDatabaseCount('activity_logs', 0);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 49.98]);
});

test('checkout rejects injected price and unit overrides', function (string $key) {
    $fixtures = createRetiredAlterationFixtures();

    $this->actingAs($fixtures['clerk'])->post(route('sales.store'), [
        'items' => [['product_unit_id' => $fixtures['base']->product_unit_id, 'quantity' => 1, $key => 1]], 'payment' => 100,
    ])->assertSessionHasErrors('items.0');

    $this->assertDatabaseCount('sale_items', 0);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 49.98]);
})->with(['unit_price', 'selling_unit_id', 'base_quantity', 'alteration']);

test('historical altered invoices retain their selling quantity unit and price', function () {
    $fixtures = createRetiredAlterationFixtures();
    $sale = createHistoricalAlteredSale($fixtures, false);

    $this->actingAs($fixtures['owner'])->get(route('sales.receipt', $sale))->assertRedirect(route('sales.create'));
    $this->get(route('sales.create'))->assertOk()->assertSee('Piece')->assertSee('₱2.00')->assertSee('₱10.00')
        ->assertViewHas('completedSale', fn (Sale $receipt): bool => $receipt->items->first()->alteration['quantity'] === 5);

    $this->assertDatabaseHas('sale_items', ['sale_id' => $sale->sale_id, 'quantity' => 0.02, 'unit_price' => 2, 'subtotal' => 10]);
});

test('cancelling a historical altered delivery restores its measured stock exactly once', function () {
    $fixtures = createRetiredAlterationFixtures();
    $sale = createHistoricalAlteredSale($fixtures, true);
    $fixtures['base']->update(['conversion_factor' => 2]);

    $this->actingAs($fixtures['owner'])->patch(route('sales.cancel-delivery', $sale), ['delivery_cancel_reason' => 'Customer cancelled'])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 50]);
    $this->assertDatabaseHas('sales', ['sale_id' => $sale->sale_id, 'status' => 'CANCELLED']);
    $this->patch(route('sales.cancel-delivery', $sale), ['delivery_cancel_reason' => 'Duplicate attempt'])->assertSessionHas('error');
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 50]);
});
