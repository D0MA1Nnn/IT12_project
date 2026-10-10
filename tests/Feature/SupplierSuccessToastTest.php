<?php

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['services.online_backup.path' => null]);

    $this->actingAs(User::forceCreate([
        'username' => 'owner', 'password_hash' => Hash::make('secret'),
        'role' => 'OWNER', 'is_active' => true,
    ]));
});

test('supplier saves show one temporary success toast instead of a banner', function (string $method, string $message) {
    $supplier = Supplier::create(['supplier_name' => 'Existing Supplier', 'is_active' => true]);
    $endpoint = $method === 'post' ? route('suppliers.store') : route('suppliers.update', $supplier);

    $this->{$method}($endpoint, [
        'supplier_name' => 'Saved Supplier', 'contact_person' => 'Juan Dela Cruz',
        'contact_number' => '09123456789', 'email' => 'supplier@gmail.com', 'address' => 'Davao City',
    ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('suppliers.index'));

    $response = $this->get(route('suppliers.index'))
        ->assertOk()
        ->assertSee('class="success-toast no-print"', false)
        ->assertSee('role="status" aria-live="polite"', false)
        ->assertSee($message)
        ->assertDontSee('<div class="alert">', false);

    expect(substr_count($response->getContent(), $message))->toBe(1);
    $this->assertDatabaseHas('suppliers', ['supplier_name' => 'Saved Supplier']);
})->with([
    'add' => ['post', 'Supplier added successfully.'],
    'edit' => ['put', 'Supplier updated successfully.'],
]);

test('supplier success messages are escaped', function () {
    $message = '<script>alert("unsafe")</script>';

    $this->withSession(['success' => $message])->get(route('suppliers.index'))
        ->assertOk()
        ->assertSee($message)
        ->assertDontSee($message, false);
});

test('supplier validation errors remain visible without a success toast', function () {
    $this->from(route('suppliers.index'))->post(route('suppliers.store'), [
        'supplier_name' => 'Invalid Supplier', 'contact_number' => '98734739437',
        'contact_person' => 'Juan Dela Cruz', 'email' => 'supplier@gmail.com', 'address' => 'Davao City',
    ])->assertSessionHasErrors('contact_number');

    $this->get(route('suppliers.index'))
        ->assertOk()
        ->assertSee('class="error-toast no-print"', false)
        ->assertSee('Contact number must start with 09 and contain exactly 11 digits.')
        ->assertDontSee('class="success-toast no-print"', false);

    $this->assertDatabaseCount('suppliers', 0);
});

test('supplier pages without a success message show no toast', function () {
    $this->get(route('suppliers.index'))
        ->assertOk()
        ->assertDontSee('class="success-toast no-print"', false);
});

test('management pages show one shared success popup instead of an inline banner', function (string $routeName) {
    $message = 'Operation completed successfully.';

    $response = $this->withSession(['success' => $message])->get(route($routeName))
        ->assertSee('class="success-toast no-print"', false)
        ->assertSee('role="status" aria-live="polite"', false)
        ->assertSee($message)
        ->assertDontSee('<div class="alert">', false)
        ->assertDontSee('<div class="alert success"', false);

    expect(substr_count($response->getContent(), 'data-success-toast role='))->toBe(1);
    expect(substr_count($response->getContent(), $message))->toBe(1);
})->with([
    'dashboard' => 'dashboard',
    'products' => 'products.index',
    'categories' => 'categories.index',
    'units of measure' => 'units.index',
    'inventory' => 'inventory.index',
    'suppliers' => 'suppliers.index',
    'purchases' => 'purchases.index',
    'cashiering' => 'sales.create',
    'delivery' => 'sales.index',
    'sales report' => 'sales.report',
    'users' => 'users.index',
    'transactions' => 'transactions.index',
    'activity logs' => 'activity.index',
    'backup' => 'backup.index',
]);

test('management pages without a success message do not show a popup', function (string $routeName) {
    $this->get(route($routeName))->assertDontSee('class="success-toast no-print"', false);
})->with(['products.index', 'purchases.index', 'sales.create', 'sales.index']);

test('management errors use temporary error popups instead of success popups', function (string $routeName) {
    $this->withSession(['error' => 'Unable to save changes.'])->get(route($routeName))
        ->assertSee('class="error-toast no-print"', false)
        ->assertSee('Unable to save changes.')
        ->assertDontSee('class="success-toast no-print"', false);
})->with(['products.index', 'purchases.index', 'sales.create', 'sales.index']);
