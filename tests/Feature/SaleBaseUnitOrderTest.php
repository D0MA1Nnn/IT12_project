<?php

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['services.online_backup.path' => null]);
});

/**
 * @return array{owner: User, product: Product, base: ProductUnit, bag: ProductUnit}
 */
function createBaseUnitOrderFixtures(): array
{
    $owner = User::forceCreate([
        'username' => 'owner',
        'password_hash' => Hash::make('owner123'),
        'role' => 'OWNER',
        'is_active' => true,
    ]);
    $category = Category::create(['category_name' => 'Materials', 'is_active' => true]);
    $kilogram = UnitOfMeasure::create([
        'unit_name' => 'Kilogram', 'unit_symbol' => 'kg', 'unit_type' => 'Weight', 'is_active' => true,
    ]);
    $bagUnit = UnitOfMeasure::create([
        'unit_name' => 'Bag', 'unit_symbol' => 'bag', 'unit_type' => 'Count', 'is_active' => true,
    ]);
    $product = Product::create([
        'category_id' => $category->category_id, 'product_name' => 'Portland Cement', 'is_active' => true,
    ]);
    Inventory::create([
        'product_id' => $product->product_id, 'quantity_on_hand' => 100, 'reorder_level' => 5,
    ]);
    $base = ProductUnit::create([
        'product_id' => $product->product_id, 'unit_id' => $kilogram->unit_id,
        'selling_price' => 8, 'purchase_cost' => 7, 'conversion_factor' => 1,
        'is_base_unit' => true, 'is_active' => true,
    ]);
    $bag = ProductUnit::create([
        'product_id' => $product->product_id, 'unit_id' => $bagUnit->unit_id,
        'selling_price' => 320, 'purchase_cost' => 280, 'conversion_factor' => 40,
        'is_base_unit' => false, 'is_active' => true,
    ]);

    return compact('owner', 'product', 'base', 'bag');
}

test('a product sold in multiple units is saved as one base-unit invoice line', function (array $selections, float $quantity, float $total, float $remainingStock) {
    $fixtures = createBaseUnitOrderFixtures();
    $items = array_map(fn (array $selection): array => [
        'product_unit_id' => $fixtures[$selection[0]]->product_unit_id,
        'quantity' => $selection[1],
    ], $selections);

    $this->actingAs($fixtures['owner'])->post(route('sales.store'), [
        'items' => $items, 'payment' => 1000,
    ])->assertRedirect(route('sales.create'))->assertSessionHasNoErrors();

    $sale = Sale::firstOrFail();
    $this->assertDatabaseCount('sale_items', 1);
    $this->assertDatabaseHas('sale_items', [
        'sale_id' => $sale->sale_id, 'product_unit_id' => $fixtures['base']->product_unit_id,
        'quantity' => $quantity, 'unit_price' => 8, 'subtotal' => $total,
    ]);
    $this->assertDatabaseHas('inventories', [
        'product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => $remainingStock,
    ]);
    $this->assertDatabaseHas('sales', ['sale_id' => $sale->sale_id, 'total_amount' => $total, 'status' => 'COMPLETED']);
    $this->assertDatabaseCount('activity_logs', 1);

    $this->get(route('sales.create'))->assertViewHas('completedSale', function (Sale $receipt) use ($quantity): bool {
        return $receipt->items->count() === 1
            && (float) $receipt->items->first()->quantity === $quantity
            && $receipt->items->first()->productUnit->unit->unit_name === 'Kilogram';
    });
})->with([
    'one bag plus half a bag' => [[['bag', 1], ['bag', 0.5]], 60.0, 480.0, 40.0],
    'one bag plus half a kilogram' => [[['bag', 1], ['base', 0.5]], 40.5, 324.0, 59.5],
    'repeated base units' => [[['base', 0.5], ['base', 0.5]], 1.0, 8.0, 99.0],
    'fractional base quantity' => [[['base', 0.125]], 0.125, 1.0, 99.875],
]);

test('cashiering displays available base stock without changing stored inventory', function () {
    $fixtures = createBaseUnitOrderFixtures();

    $response = $this->actingAs($fixtures['owner'])->get(route('sales.create'));

    $response->assertSee('Available stock:')
        ->assertDontSee('Base stock:')
        ->assertSee('data-base-stock="100"', false)
        ->assertSee('data-available-stock', false)
        ->assertSee('data-stock-badge', false);
    $this->assertDatabaseHas('inventories', [
        'product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 100,
    ]);
    $this->assertDatabaseCount('sales', 0);
    $this->assertDatabaseCount('sale_items', 0);
});

test('sales pages show a success confirmation only once', function (string $routeName) {
    $fixtures = createBaseUnitOrderFixtures();
    $message = 'Delivery marked as delivered successfully. Online backup saved.';

    $response = $this->actingAs($fixtures['owner'])
        ->withSession(['success' => $message])
        ->get(route($routeName));

    $response->assertSee($message);
    expect(substr_count($response->getContent(), $message))->toBe(1);
})->with([
    'delivery keeps its page confirmation' => ['sales.index'],
    'sales report keeps the shared confirmation' => ['sales.report'],
]);

test('mixed units cannot consume more than the product base stock', function () {
    $fixtures = createBaseUnitOrderFixtures();

    $this->actingAs($fixtures['owner'])->post(route('sales.store'), [
        'items' => [
            ['product_unit_id' => $fixtures['bag']->product_unit_id, 'quantity' => 2],
            ['product_unit_id' => $fixtures['base']->product_unit_id, 'quantity' => 21],
        ], 'payment' => 1000,
    ])->assertSessionHasErrors(['items' => 'Insufficient stock for Portland Cement.']);

    $this->assertDatabaseCount('sales', 0);
    $this->assertDatabaseCount('sale_items', 0);
    $this->assertDatabaseCount('activity_logs', 0);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 100]);
});

test('a product without an active base unit cannot be sold', function () {
    $fixtures = createBaseUnitOrderFixtures();
    $fixtures['base']->update(['is_active' => false]);

    $this->actingAs($fixtures['owner'])->post(route('sales.store'), [
        'items' => [['product_unit_id' => $fixtures['bag']->product_unit_id, 'quantity' => 1]],
        'payment' => 1000,
    ])->assertSessionHasErrors(['items' => 'An active base unit with a conversion of 1 is required for Portland Cement.']);

    $this->assertDatabaseCount('sales', 0);
    $this->assertDatabaseCount('sale_items', 0);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 100]);
});

test('payment below the normalized total leaves stock unchanged', function () {
    $fixtures = createBaseUnitOrderFixtures();

    $this->actingAs($fixtures['owner'])->post(route('sales.store'), [
        'items' => [
            ['product_unit_id' => $fixtures['bag']->product_unit_id, 'quantity' => 1],
            ['product_unit_id' => $fixtures['base']->product_unit_id, 'quantity' => 0.5],
        ], 'payment' => 323,
    ])->assertSessionHasErrors(['payment' => 'Customer payment is less than the total amount.']);

    $this->assertDatabaseCount('sales', 0);
    $this->assertDatabaseCount('sale_items', 0);
    $this->assertDatabaseCount('activity_logs', 0);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 100]);
});

test('different products remain separate while each product uses one base-unit line', function () {
    $fixtures = createBaseUnitOrderFixtures();
    $otherProduct = Product::create([
        'category_id' => $fixtures['product']->category_id, 'product_name' => 'Gravel', 'is_active' => true,
    ]);
    Inventory::create(['product_id' => $otherProduct->product_id, 'quantity_on_hand' => 100]);
    $otherBase = ProductUnit::create([
        'product_id' => $otherProduct->product_id, 'unit_id' => $fixtures['base']->unit_id,
        'selling_price' => 10, 'conversion_factor' => 1, 'is_base_unit' => true, 'is_active' => true,
    ]);
    $otherBag = ProductUnit::create([
        'product_id' => $otherProduct->product_id, 'unit_id' => $fixtures['bag']->unit_id,
        'selling_price' => 200, 'conversion_factor' => 20, 'is_base_unit' => false, 'is_active' => true,
    ]);

    $this->actingAs($fixtures['owner'])->post(route('sales.store'), [
        'items' => [
            ['product_unit_id' => $fixtures['bag']->product_unit_id, 'quantity' => 1],
            ['product_unit_id' => $fixtures['base']->product_unit_id, 'quantity' => 0.5],
            ['product_unit_id' => $otherBag->product_unit_id, 'quantity' => 2],
            ['product_unit_id' => $otherBase->product_unit_id, 'quantity' => 3],
        ], 'payment' => 1000,
    ])->assertRedirect(route('sales.create'))->assertSessionHasNoErrors();

    $this->assertDatabaseCount('sale_items', 2);
    $this->assertDatabaseHas('sale_items', ['product_unit_id' => $fixtures['base']->product_unit_id, 'quantity' => 40.5, 'subtotal' => 324]);
    $this->assertDatabaseHas('sale_items', ['product_unit_id' => $otherBase->product_unit_id, 'quantity' => 43, 'subtotal' => 430]);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 59.5]);
    $this->assertDatabaseHas('inventories', ['product_id' => $otherProduct->product_id, 'quantity_on_hand' => 57]);
    $this->assertDatabaseHas('sales', ['total_amount' => 754]);
});

test('delivery orders combine product quantities and include the delivery fee once', function () {
    $fixtures = createBaseUnitOrderFixtures();

    $this->actingAs($fixtures['owner'])->post(route('sales.store'), [
        'items' => [
            ['product_unit_id' => $fixtures['bag']->product_unit_id, 'quantity' => 1],
            ['product_unit_id' => $fixtures['base']->product_unit_id, 'quantity' => 0.5],
        ], 'payment' => 334, 'delivery_required' => 1, 'delivery_fee' => 10,
        'customer_name' => 'Test Customer', 'customer_contact_number' => '09171234567',
        'delivery_address' => 'Davao City',
    ])->assertRedirect(route('sales.create'))->assertSessionHasNoErrors()->assertSessionHas('receipt_change', 0);

    $this->assertDatabaseCount('sale_items', 1);
    $this->assertDatabaseHas('sale_items', ['product_unit_id' => $fixtures['base']->product_unit_id, 'quantity' => 40.5, 'subtotal' => 324]);
    $this->assertDatabaseHas('sales', ['total_amount' => 334, 'delivery_fee' => 10, 'status' => 'PENDING']);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 59.5]);
});

/**
 * @param  array{owner: User, product: Product, base: ProductUnit, bag: ProductUnit}  $fixtures
 * @return array<string, mixed>
 */
function cashOnDeliveryOrderPayload(array $fixtures): array
{
    return [
        'items' => [
            ['product_unit_id' => $fixtures['bag']->product_unit_id, 'quantity' => 1],
            ['product_unit_id' => $fixtures['bag']->product_unit_id, 'quantity' => 0.5],
        ],
        'payment_method' => 'COD', 'delivery_required' => 1, 'delivery_fee' => 10,
        'customer_name' => 'Test Customer', 'customer_contact_number' => '09171234567',
        'delivery_address' => 'Davao City',
    ];
}

test('COD reserves stock without payment and prints the unpaid amount due', function () {
    $fixtures = createBaseUnitOrderFixtures();

    $this->actingAs($fixtures['owner'])->post(route('sales.store'), cashOnDeliveryOrderPayload($fixtures))
        ->assertRedirect(route('sales.create'))->assertSessionHasNoErrors();

    $sale = Sale::firstOrFail();
    $this->assertDatabaseHas('sales', [
        'sale_id' => $sale->sale_id, 'status' => 'PENDING', 'delivery_status' => 'PENDING',
        'payment_method' => 'COD', 'payment_status' => 'UNPAID', 'payment_received' => 0,
        'paid_at' => null, 'total_amount' => 490,
    ]);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 40]);
    $this->assertDatabaseCount('sale_items', 1);
    expect($sale->amountDue())->toBe(490.0);

    $this->get(route('sales.create'))
        ->assertSee('Cash on Delivery (COD)')->assertSee('Amount Due on Delivery')
        ->assertSee('UNPAID — Collect payment after the customer receives the products.')
        ->assertSee('id="mAmountDue"', false)->assertDontSee('id="mChange"', false)
        ->assertDontSee('id="mPayment"', false);
});

test('marking COD delivered automatically pays the products and delivery fee without deducting stock again', function () {
    $fixtures = createBaseUnitOrderFixtures();
    $this->freezeTime();
    $this->actingAs($fixtures['owner'])->post(route('sales.store'), cashOnDeliveryOrderPayload($fixtures))->assertSessionHasNoErrors();
    $sale = Sale::firstOrFail();

    $this->get(route('sales.index'))->assertSee('Mark Delivered')->assertSee('including the delivery fee')->assertDontSee('Record COD Payment');
    $this->get(route('sales.report'))->assertViewHas('totalAmount', 0.0);
    $this->get(route('dashboard'))->assertViewHas('todaySalesAmount', 0)->assertViewHas('pendingDeliveryCount', 1);

    $this->patch(route('sales.complete-delivery', $sale))
        ->assertRedirect(route('sales.create'))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('sales', [
        'sale_id' => $sale->sale_id, 'status' => 'COMPLETED', 'delivery_status' => 'DELIVERED',
        'payment_status' => 'PAID', 'payment_received' => 490, 'total_amount' => 490, 'delivery_fee' => 10,
    ]);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 40]);
    expect($sale->fresh()->amountDue())->toBe(0.0);
    expect($sale->fresh()->paymentChange())->toBe(0.0);
    expect($sale->fresh()->paid_at->toDateTimeString())->toBe(now()->toDateTimeString());
    $this->assertDatabaseCount('activity_logs', 2);
    $this->get(route('sales.create'))->assertSee('Payment Received')->assertSee('₱490.00')->assertSee('₱10.00')->assertSee('₱0.00')->assertDontSee('id="mAmountDue"', false);
    $this->get(route('sales.report'))->assertViewHas('totalAmount', 490.0);
    $this->get(route('dashboard'))->assertViewHas('todaySalesAmount', 490)->assertViewHas('pendingDeliveryCount', 0);
    $this->get(route('sales.index'))->assertViewHas('sales', fn ($sales): bool => $sales->total() === 0);
});

test('COD cannot be selected for a walk-in order', function () {
    $fixtures = createBaseUnitOrderFixtures();
    $payload = cashOnDeliveryOrderPayload($fixtures);
    $payload['delivery_required'] = 0;

    $this->actingAs($fixtures['owner'])->post(route('sales.store'), $payload)
        ->assertSessionHasErrors(['delivery_required' => 'Cash on delivery requires a delivery order.']);

    $this->assertDatabaseCount('sales', 0);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 100]);
});

test('COD cannot falsely record an upfront payment or trust a submitted paid status', function () {
    $fixtures = createBaseUnitOrderFixtures();
    $payload = cashOnDeliveryOrderPayload($fixtures);
    $payload['payment'] = 490;
    $payload['payment_status'] = 'PAID';

    $this->actingAs($fixtures['owner'])->post(route('sales.store'), $payload)
        ->assertSessionHasErrors(['payment' => 'For cash on delivery, record the payment after the customer receives the products.']);

    $this->assertDatabaseCount('sales', 0);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 100]);
});

test('COD payment cannot be collected before delivery', function () {
    $fixtures = createBaseUnitOrderFixtures();
    $this->actingAs($fixtures['owner'])->post(route('sales.store'), cashOnDeliveryOrderPayload($fixtures))->assertSessionHasNoErrors();
    $sale = Sale::firstOrFail();

    $this->patch(route('sales.collect-payment', $sale), ['payment' => 490])
        ->assertSessionHasErrors(['sale' => 'Payment can only be recorded for delivered, unpaid COD orders.']);

    $this->assertDatabaseHas('sales', ['sale_id' => $sale->sale_id, 'status' => 'PENDING', 'payment_status' => 'UNPAID', 'payment_received' => 0]);
    $this->assertDatabaseCount('activity_logs', 1);
});

test('COD ignores client submitted payment and delivery status fields', function () {
    $fixtures = createBaseUnitOrderFixtures();
    $payload = cashOnDeliveryOrderPayload($fixtures);
    $payload['payment_status'] = 'PAID';
    $payload['payment_received'] = 490;
    $payload['delivery_status'] = 'DELIVERED';
    $payload['status'] = 'COMPLETED';

    $this->actingAs($fixtures['owner'])->post(route('sales.store'), $payload)->assertSessionHasNoErrors();

    $this->assertDatabaseHas('sales', [
        'status' => 'PENDING', 'delivery_status' => 'PENDING',
        'payment_status' => 'UNPAID', 'payment_received' => 0, 'paid_at' => null,
    ]);
});

test('completed COD cannot be delivered twice or cancelled to restore stock', function () {
    $fixtures = createBaseUnitOrderFixtures();
    $this->actingAs($fixtures['owner'])->post(route('sales.store'), cashOnDeliveryOrderPayload($fixtures))->assertSessionHasNoErrors();
    $sale = Sale::firstOrFail();
    $this->patch(route('sales.complete-delivery', $sale))->assertSessionHasNoErrors();

    $this->patch(route('sales.complete-delivery', $sale))->assertSessionHasErrors('sale');
    $this->patch(route('sales.cancel-delivery', $sale), ['delivery_cancel_reason' => 'Already received'])->assertSessionHas('error');
    $this->patch(route('sales.collect-payment', $sale), ['payment' => 500])->assertSessionHasErrors('sale');

    $this->assertDatabaseHas('sales', ['sale_id' => $sale->sale_id, 'status' => 'COMPLETED', 'delivery_status' => 'DELIVERED', 'payment_status' => 'PAID', 'payment_received' => 490]);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 40]);
    $this->assertDatabaseCount('activity_logs', 2);
});

test('COD collection rejects payment below the amount due', function () {
    $fixtures = createBaseUnitOrderFixtures();
    $this->actingAs($fixtures['owner'])->post(route('sales.store'), cashOnDeliveryOrderPayload($fixtures))->assertSessionHasNoErrors();
    $sale = Sale::firstOrFail();
    $sale->update(['delivery_status' => 'DELIVERED']);

    $this->patch(route('sales.collect-payment', $sale), ['payment' => 489])
        ->assertSessionHasErrors(['payment' => 'The amount collected is less than the total amount due.']);

    $this->assertDatabaseHas('sales', ['sale_id' => $sale->sale_id, 'status' => 'PENDING', 'payment_status' => 'UNPAID', 'payment_received' => 0]);
    $this->assertDatabaseCount('activity_logs', 1);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 40]);
});

test('COD collection cannot be recorded twice', function () {
    $fixtures = createBaseUnitOrderFixtures();
    $this->actingAs($fixtures['owner'])->post(route('sales.store'), cashOnDeliveryOrderPayload($fixtures))->assertSessionHasNoErrors();
    $sale = Sale::firstOrFail();
    $sale->update(['delivery_status' => 'DELIVERED']);
    $this->patch(route('sales.collect-payment', $sale), ['payment' => 490])->assertSessionHasNoErrors();

    $this->patch(route('sales.collect-payment', $sale), ['payment' => 500])->assertSessionHasErrors('sale');

    $this->assertDatabaseHas('sales', ['sale_id' => $sale->sale_id, 'payment_received' => 490, 'status' => 'COMPLETED']);
    $this->assertDatabaseCount('activity_logs', 2);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 40]);
});

test('an older delivered unpaid COD order can confirm full payment without entering an amount', function () {
    $fixtures = createBaseUnitOrderFixtures();
    $this->actingAs($fixtures['owner'])->post(route('sales.store'), cashOnDeliveryOrderPayload($fixtures))->assertSessionHasNoErrors();
    $sale = Sale::firstOrFail();
    $sale->update(['delivery_status' => 'DELIVERED']);

    $this->get(route('sales.index'))
        ->assertSee('Confirm Paid')->assertSee('Confirm COD Payment?')->assertSee('Yes, Confirm Paid')
        ->assertSee('action="'.route('sales.collect-payment', $sale).'"', false)
        ->assertDontSee('Record COD Payment')->assertDontSee('Amount Collected');
    $this->patch(route('sales.collect-payment', $sale))
        ->assertRedirect(route('sales.create'))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('sales', [
        'sale_id' => $sale->sale_id, 'status' => 'COMPLETED', 'delivery_status' => 'DELIVERED',
        'payment_status' => 'PAID', 'payment_received' => 490, 'total_amount' => 490, 'delivery_fee' => 10,
    ]);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 40]);
    $this->get(route('sales.create'))->assertSee('Payment Received')->assertSee('₱490.00')->assertDontSee('id="mAmountDue"', false);
});

test('cancelling a pending COD order returns stock once without recording payment', function () {
    $fixtures = createBaseUnitOrderFixtures();
    $this->actingAs($fixtures['owner'])->post(route('sales.store'), cashOnDeliveryOrderPayload($fixtures))->assertSessionHasNoErrors();
    $sale = Sale::firstOrFail();

    $this->patch(route('sales.cancel-delivery', $sale), ['delivery_cancel_reason' => 'Customer cancelled'])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('sales', ['sale_id' => $sale->sale_id, 'status' => 'CANCELLED', 'delivery_status' => 'CANCELLED', 'payment_status' => 'UNPAID', 'payment_received' => 0]);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 100]);
    $this->patch(route('sales.cancel-delivery', $sale), ['delivery_cancel_reason' => 'Customer cancelled'])->assertSessionHas('error');
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 100]);
    $this->patch(route('sales.collect-payment', $sale), ['payment' => 490])->assertSessionHasErrors('sale');
});

test('prepaid delivery completes without asking for another payment', function () {
    $fixtures = createBaseUnitOrderFixtures();
    $payload = cashOnDeliveryOrderPayload($fixtures);
    $payload['payment_method'] = 'PAY_NOW';
    $payload['payment'] = 500;
    $this->actingAs($fixtures['owner'])->post(route('sales.store'), $payload)->assertSessionHasNoErrors();
    $sale = Sale::firstOrFail();

    $this->patch(route('sales.complete-delivery', $sale))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('sales', ['sale_id' => $sale->sale_id, 'status' => 'COMPLETED', 'delivery_status' => 'DELIVERED', 'payment_status' => 'PAID', 'payment_received' => 500]);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 40]);
    $this->get(route('sales.receipt', $sale))->assertRedirect(route('sales.create'));
    $this->get(route('sales.create'))->assertSee('Payment Received')->assertSee('₱500.00')->assertSee('₱10.00');
});

test('guests cannot record COD payment or view a protected invoice', function () {
    $fixtures = createBaseUnitOrderFixtures();
    $sale = Sale::create([
        'user_id' => $fixtures['owner']->user_id, 'sale_date' => now(), 'total_amount' => 490,
        'status' => 'PENDING', 'delivery_required' => true, 'delivery_status' => 'DELIVERED',
        'payment_method' => 'COD', 'payment_status' => 'UNPAID', 'payment_received' => 0,
    ]);

    $this->patch(route('sales.collect-payment', $sale), ['payment' => 490])->assertRedirect(route('login'));
    $this->patch(route('sales.complete-delivery', $sale))->assertRedirect(route('login'));
    $this->get(route('sales.receipt', $sale))->assertRedirect(route('login'));
    $this->assertDatabaseHas('sales', ['sale_id' => $sale->sale_id, 'payment_status' => 'UNPAID', 'payment_received' => 0]);
});

test('delivery actions remove redundant details and show a confirmation dialog before completing an order', function (string $paymentMethod, ?float $payment, string $paymentStatus, string $message, string $confirmButton) {
    $fixtures = createBaseUnitOrderFixtures();
    $payload = cashOnDeliveryOrderPayload($fixtures);
    $payload['payment_method'] = $paymentMethod;
    $payload['payment'] = $payment;
    $this->actingAs($fixtures['owner'])->post(route('sales.store'), $payload)->assertSessionHasNoErrors();
    $sale = Sale::firstOrFail();

    $this->get(route('sales.index'))
        ->assertSee('View Invoice')->assertDontSee('View Details')->assertDontSee('Delivery Details')
        ->assertSee('data-open-modal="confirmDeliveryModal'.$sale->sale_id.'"', false)
        ->assertSee('role="dialog"', false)->assertSee('Complete Delivery?')
        ->assertSee($message)->assertSee($confirmButton)->assertSee('Go Back')
        ->assertSee('action="'.route('sales.complete-delivery', $sale).'"', false)
        ->assertDontSee('onsubmit="return confirm(this.dataset.confirm)"', false);

    $this->assertDatabaseHas('sales', [
        'sale_id' => $sale->sale_id, 'status' => 'PENDING', 'delivery_status' => 'PENDING',
        'payment_status' => $paymentStatus, 'payment_received' => $payment ?? 0,
    ]);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 40]);
    $this->assertDatabaseCount('activity_logs', 1);
})->with([
    'COD confirms delivery and collection' => ['COD', null, 'UNPAID', 'Only confirm after collecting the full payment.', 'Yes, Delivered & Paid'],
    'prepaid confirms delivery only' => ['PAY_NOW', 500.0, 'PAID', 'Payment is already recorded.', 'Yes, Mark Delivered'],
]);
