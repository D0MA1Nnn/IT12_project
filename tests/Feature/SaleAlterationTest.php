<?php

use App\Models\ActivityLog;
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
use Illuminate\Support\Facades\RateLimiter;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['services.online_backup.path' => null]);
});

/** @return array{owner: User, clerk: User, product: Product, base: ProductUnit, piece: UnitOfMeasure} */
function createSaleAlterationFixtures(): array
{
    $owner = User::forceCreate(['username' => 'approval-owner', 'password_hash' => Hash::make('owner-secret'), 'role' => 'OWNER', 'is_active' => true]);
    $clerk = User::forceCreate(['username' => 'approval-clerk', 'password_hash' => Hash::make('clerk-secret'), 'role' => 'SALES_CLERK', 'is_active' => true]);
    $category = Category::create(['category_name' => 'Hardware', 'is_active' => true]);
    $kilogram = UnitOfMeasure::create(['unit_name' => 'Kilogram', 'unit_symbol' => 'kg', 'unit_type' => 'Weight', 'is_active' => true]);
    $piece = UnitOfMeasure::create(['unit_name' => 'Piece', 'unit_symbol' => 'pc', 'unit_type' => 'Count', 'is_active' => true]);
    $product = Product::create(['category_id' => $category->category_id, 'product_name' => 'Common Nail 2"', 'is_active' => true]);
    Inventory::create(['product_id' => $product->product_id, 'quantity_on_hand' => 50, 'reorder_level' => 5]);
    $base = ProductUnit::create([
        'product_id' => $product->product_id, 'unit_id' => $kilogram->unit_id, 'conversion_factor' => 1,
        'selling_price' => 95, 'purchase_cost' => 80, 'is_base_unit' => true, 'is_active' => true,
    ]);
    RateLimiter::clear('sale-alteration:'.$clerk->user_id.'|127.0.0.1');
    RateLimiter::clear('sale-alteration:'.$owner->user_id.'|127.0.0.1');

    return compact('owner', 'clerk', 'product', 'base', 'piece');
}

/** @param array{owner: User, clerk: User, product: Product, base: ProductUnit, piece: UnitOfMeasure} $fixtures
 *  @return array<string, int|float|string> */
function saleAlterationPayload(array $fixtures): array
{
    return [
        'admin_username' => $fixtures['owner']->username, 'password' => 'owner-secret',
        'product_unit_id' => $fixtures['base']->product_unit_id, 'selling_unit_id' => $fixtures['piece']->unit_id,
        'quantity' => 5, 'base_quantity' => 0.02, 'unit_price' => 2, 'original_quantity' => 1,
        'reason' => 'Customer wants five pieces; weighed at 20 grams.',
    ];
}

describe('retired Alter checkout workflow', function () {
    test('owner approval sells five nails as pieces and deducts only their measured kilograms', function (string $cashier) {
        $fixtures = createSaleAlterationFixtures();

        $approval = $this->actingAs($fixtures[$cashier])->postJson(route('sales.alterations.store'), saleAlterationPayload($fixtures));

        $approval->assertOk()->assertJsonPath('alteration.quantity', 5)
            ->assertJsonPath('alteration.base_quantity', 0.02)->assertJsonPath('alteration.subtotal', 10);
        $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 50]);
        $this->assertDatabaseCount('sale_items', 0);
        $token = $approval->json('token');
        $approval->assertDontSee('owner-secret')->assertDontSee('password_hash');

        $this->post(route('sales.store'), [
            'items' => [['product_unit_id' => $fixtures['base']->product_unit_id, 'quantity' => 0.02, 'alteration_token' => $token]],
            'payment' => 20,
        ])->assertRedirect(route('sales.create'))->assertSessionHasNoErrors()
            ->assertSessionMissing('sale_alterations.'.$token);

        $this->assertDatabaseHas('sale_items', ['quantity' => 0.02, 'unit_price' => 2, 'subtotal' => 10]);
        $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 49.98]);
        $this->assertDatabaseHas('product_units', ['product_unit_id' => $fixtures['base']->product_unit_id, 'selling_price' => 95, 'conversion_factor' => 1]);
        $this->assertDatabaseHas('sales', ['total_amount' => 10, 'status' => 'COMPLETED']);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $fixtures[$cashier]->user_id, 'action' => 'ALTER']);
        expect(SaleItem::firstOrFail()->alteration['approved_by'])->toBe($fixtures['owner']->user_id);
        expect(ActivityLog::where('action', 'ALTER')->firstOrFail()->description)->toContain('approval-owner', '20 grams');
        $this->get(route('sales.create'))->assertSee('Piece')->assertSee('₱2.00')->assertSee('₱10.00')
            ->assertViewHas('completedSale', fn (Sale $sale): bool => $sale->items->first()->alteration['quantity'] === 5);
    })->with(['owner must also approve' => ['owner'], 'staff needs owner approval' => ['clerk']]);

    test('staff password or an inactive owner cannot approve alterations', function (string $approver) {
        $fixtures = createSaleAlterationFixtures();
        $payload = saleAlterationPayload($fixtures);
        $payload['admin_username'] = $fixtures[$approver]->username;
        $payload['password'] = $approver === 'clerk' ? 'clerk-secret' : 'owner-secret';
        if ($approver === 'owner') {
            $fixtures['owner']->update(['is_active' => false]);
        }

        $this->actingAs($fixtures['clerk'])->postJson(route('sales.alterations.store'), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->assertDatabaseCount('sales', 0);
        expect(session('sale_alterations', []))->toBe([]);
    })->with(['staff credentials' => ['clerk'], 'inactive owner' => ['owner']]);

    test('guests cannot request alteration approval', function () {
        $fixtures = createSaleAlterationFixtures();

        $this->postJson(route('sales.alterations.store'), saleAlterationPayload($fixtures))->assertUnauthorized();

        $this->assertDatabaseCount('sales', 0);
    });

    test('invalid measured weight and fractional pieces are rejected', function (string $field, mixed $value) {
        $fixtures = createSaleAlterationFixtures();
        $payload = saleAlterationPayload($fixtures);
        $payload[$field] = $value;

        $this->actingAs($fixtures['clerk'])->postJson(route('sales.alterations.store'), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 50]);
        expect(session('sale_alterations', []))->toBe([]);
    })->with([
        'missing measured weight' => ['base_quantity', null],
        'zero measured weight' => ['base_quantity', 0],
        'more than stock' => ['base_quantity', 51],
        'excessive weight precision' => ['base_quantity', 0.0000001],
        'fractional piece' => ['quantity', 0.5],
        'negative price' => ['unit_price', -1],
        'missing reason' => ['reason', ''],
    ]);

    test('tampered or expired approval never changes inventory', function (string $tampering) {
        $fixtures = createSaleAlterationFixtures();
        $approval = $this->actingAs($fixtures['clerk'])->postJson(route('sales.alterations.store'), saleAlterationPayload($fixtures));
        $approval->assertOk();
        $token = $approval->json('token');
        $item = ['product_unit_id' => $fixtures['base']->product_unit_id, 'quantity' => 0.02, 'alteration_token' => $token];
        if ($tampering === 'quantity') {
            $item['quantity'] = 0.01;
        } elseif ($tampering === 'token') {
            $item['alteration_token'] = str_repeat('x', 64);
        } elseif ($tampering === 'cashier') {
            $this->actingAs($fixtures['owner']);
        } elseif ($tampering === 'expired') {
            $this->travel(16)->minutes();
        } else {
            $fixtures['owner']->update(['is_active' => false]);
        }

        $this->post(route('sales.store'), ['items' => [$item], 'payment' => 20])->assertSessionHasErrors('items');

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 50]);
    })->with(['quantity', 'token', 'cashier', 'expired', 'owner revoked']);

    test('approval is single use and cannot be mixed with an unapproved line for the same product', function () {
        $fixtures = createSaleAlterationFixtures();
        $approval = $this->actingAs($fixtures['clerk'])->postJson(route('sales.alterations.store'), saleAlterationPayload($fixtures));
        $approval->assertOk();
        $item = ['product_unit_id' => $fixtures['base']->product_unit_id, 'quantity' => 0.02, 'alteration_token' => $approval->json('token')];

        $this->post(route('sales.store'), ['items' => [$item, ['product_unit_id' => $fixtures['base']->product_unit_id, 'quantity' => 1]], 'payment' => 200])
            ->assertSessionHasErrors('items');
        $this->assertDatabaseCount('sales', 0);
        $this->post(route('sales.store'), ['items' => [$item], 'payment' => 20])->assertSessionHasNoErrors();
        $this->post(route('sales.store'), ['items' => [$item], 'payment' => 20])->assertSessionHasErrors('items');

        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 49.98]);
    });

    test('insufficient payment rolls back alterations and retains approval for a valid retry', function () {
        $fixtures = createSaleAlterationFixtures();
        $approval = $this->actingAs($fixtures['clerk'])->postJson(route('sales.alterations.store'), saleAlterationPayload($fixtures));
        $approval->assertOk();
        $token = $approval->json('token');
        $item = ['product_unit_id' => $fixtures['base']->product_unit_id, 'quantity' => 0.02, 'alteration_token' => $token];

        $this->post(route('sales.store'), ['items' => [$item], 'payment' => 1])->assertSessionHasErrors('payment')->assertSessionHas('sale_alterations.'.$token);

        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 50]);
    });

    test('altered COD order reserves and cancels the measured weight once', function () {
        $fixtures = createSaleAlterationFixtures();
        $approval = $this->actingAs($fixtures['clerk'])->postJson(route('sales.alterations.store'), saleAlterationPayload($fixtures));
        $approval->assertOk();
        $payload = [
            'items' => [['product_unit_id' => $fixtures['base']->product_unit_id, 'quantity' => 0.02, 'alteration_token' => $approval->json('token')]],
            'payment_method' => 'COD', 'delivery_required' => 1, 'delivery_fee' => 10,
            'customer_name' => 'Test Customer', 'customer_contact_number' => '09171234567', 'delivery_address' => 'Davao City',
        ];

        $this->post(route('sales.store'), $payload)->assertSessionHasNoErrors();

        $sale = Sale::firstOrFail();
        $this->assertDatabaseHas('sales', ['sale_id' => $sale->sale_id, 'total_amount' => 20, 'payment_status' => 'UNPAID']);
        $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 49.98]);
        $this->patch(route('sales.cancel-delivery', $sale), ['delivery_cancel_reason' => 'Customer cancelled'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 50]);
    });

    test('repeated wrong admin passwords are rate limited without storing the password', function () {
        $fixtures = createSaleAlterationFixtures();
        $payload = saleAlterationPayload($fixtures);
        $payload['password'] = 'wrong-secret';
        $this->actingAs($fixtures['clerk']);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson(route('sales.alterations.store'), $payload)->assertUnprocessable();
        }

        $this->postJson(route('sales.alterations.store'), saleAlterationPayload($fixtures))->assertStatus(429)->assertDontSee('wrong-secret');

        expect(json_encode(session()->all()))->not->toContain('wrong-secret', 'owner-secret');
    });

    test('an injected price without server approval cannot be saved', function () {
        $fixtures = createSaleAlterationFixtures();

        $this->actingAs($fixtures['clerk'])->post(route('sales.store'), [
            'items' => [['product_unit_id' => $fixtures['base']->product_unit_id, 'quantity' => 1, 'unit_price' => 1]], 'payment' => 100,
        ])->assertSessionHasErrors('items.0');

        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 50]);
    });

    test('approval cannot be replayed even if an old session snapshot is restored', function () {
        $fixtures = createSaleAlterationFixtures();
        $approval = $this->actingAs($fixtures['clerk'])->postJson(route('sales.alterations.store'), saleAlterationPayload($fixtures));
        $approval->assertOk();
        $token = $approval->json('token');
        $payload = ['items' => [['product_unit_id' => $fixtures['base']->product_unit_id, 'quantity' => 0.02, 'alteration_token' => $token]], 'payment' => 20];
        $this->post(route('sales.store'), $payload)->assertSessionHasNoErrors();

        $this->withSession(['sale_alterations' => [$token => $approval->json('alteration')]])
            ->post(route('sales.store'), $payload)->assertSessionHasErrors('items');

        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('sale_items', 1);
        $this->assertDatabaseHas('sale_items', ['alteration_token_hash' => hash('sha256', $token)]);
        $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 49.98]);
    });

    test('selling in the base unit cannot use a different stock deduction', function () {
        $fixtures = createSaleAlterationFixtures();
        $payload = saleAlterationPayload($fixtures);
        $payload['selling_unit_id'] = $fixtures['base']->unit_id;

        $this->actingAs($fixtures['clerk'])->postJson(route('sales.alterations.store'), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('base_quantity');

        expect(session('sale_alterations', []))->toBe([]);
    });

    test('approval cannot be used for another product', function () {
        $fixtures = createSaleAlterationFixtures();
        $otherProduct = Product::create(['category_id' => $fixtures['product']->category_id, 'product_name' => 'Other Nail', 'is_active' => true]);
        Inventory::create(['product_id' => $otherProduct->product_id, 'quantity_on_hand' => 50, 'reorder_level' => 5]);
        $otherBase = ProductUnit::create([
            'product_id' => $otherProduct->product_id, 'unit_id' => $fixtures['base']->unit_id, 'selling_price' => 95,
            'purchase_cost' => 80, 'conversion_factor' => 1, 'is_base_unit' => true, 'is_active' => true,
        ]);
        $approval = $this->actingAs($fixtures['clerk'])->postJson(route('sales.alterations.store'), saleAlterationPayload($fixtures));
        $approval->assertOk();

        $this->post(route('sales.store'), [
            'items' => [['product_unit_id' => $otherBase->product_unit_id, 'quantity' => 0.02, 'alteration_token' => $approval->json('token')]], 'payment' => 20,
        ])->assertSessionHasErrors('items');

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 50]);
        $this->assertDatabaseHas('inventories', ['product_id' => $otherProduct->product_id, 'quantity_on_hand' => 50]);
    });

    test('altered COD delivery records payment without deducting the measured weight again', function () {
        $fixtures = createSaleAlterationFixtures();
        $approval = $this->actingAs($fixtures['clerk'])->postJson(route('sales.alterations.store'), saleAlterationPayload($fixtures));
        $approval->assertOk();
        $this->post(route('sales.store'), [
            'items' => [['product_unit_id' => $fixtures['base']->product_unit_id, 'quantity' => 0.02, 'alteration_token' => $approval->json('token')]],
            'payment_method' => 'COD', 'delivery_required' => 1, 'delivery_fee' => 10,
            'customer_name' => 'Test Customer', 'customer_contact_number' => '09171234567', 'delivery_address' => 'Davao City',
        ])->assertSessionHasNoErrors();
        $sale = Sale::firstOrFail();

        $this->patch(route('sales.complete-delivery', $sale))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sales', ['sale_id' => $sale->sale_id, 'payment_status' => 'PAID', 'payment_received' => 20, 'status' => 'COMPLETED']);
        $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 49.98]);
        $this->get(route('sales.create'))->assertSee('Piece')->assertSee('Payment Received')->assertSee('₱20.00');
    });
})->skip('Alter has been removed; active removal and historical-sale coverage is in SaleAlterationRemovalTest.');
