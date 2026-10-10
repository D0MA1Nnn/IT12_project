<?php

use App\Models\Category;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['services.online_backup.path' => null]);
});

function createErrorPopupOwner(): User
{
    return User::forceCreate([
        'username' => 'popup-owner', 'password_hash' => Hash::make('secret'),
        'role' => 'OWNER', 'is_active' => true,
    ]);
}

test('management errors appear in one temporary popup without a close button or inline duplicates', function (string $routeName) {
    $owner = createErrorPopupOwner();
    $message = 'Unable to save this action.';

    $response = $this->actingAs($owner)->withSession(['error' => $message])->get(route($routeName))
        ->assertSee('class="error-toast no-print"', false)
        ->assertSee('data-error-toast role="alert"', false)
        ->assertDontSee('data-dismiss-error-toast', false)
        ->assertDontSee('aria-label="Dismiss error message"', false)
        ->assertSee($message)
        ->assertDontSee('<div class="alert err">', false)
        ->assertDontSee('<div class="alert danger"', false)
        ->assertDontSee('<div class="purchase-alert error">', false);

    expect(substr_count($response->getContent(), $message))->toBe(1);
})->with([
    'products' => 'products.index',
    'inventory' => 'inventory.index',
    'categories' => 'categories.index',
    'units' => 'units.index',
    'suppliers' => 'suppliers.index',
    'purchases' => 'purchases.index',
    'record purchase' => 'purchases.create',
    'cashiering' => 'sales.create',
    'delivery' => 'sales.index',
    'sales report' => 'sales.report',
    'users' => 'users.index',
    'backup' => 'backup.index',
]);

test('a missing product supplier shows its validation error as an inventory popup without saving', function () {
    $owner = createErrorPopupOwner();
    $category = Category::create(['category_name' => 'Hardware', 'is_active' => true]);
    $unit = UnitOfMeasure::create(['unit_name' => 'Piece', 'unit_symbol' => 'pc', 'unit_type' => 'Count', 'is_active' => true]);
    $inventoryUrl = route('inventory.index', ['category' => $category->category_id]);

    $this->actingAs($owner)->from($inventoryUrl)->post(route('products.store'), [
        'product_name' => 'Unsaved Product', 'category_id' => $category->category_id, 'unit_id' => $unit->unit_id,
        'selling_price' => 100, 'purchase_cost' => 90, 'reorder_level' => 0,
    ])->assertRedirect($inventoryUrl)
        ->assertSessionHasErrors(['supplier_id' => 'The supplier id field is required.']);

    $response = $this->get($inventoryUrl)->assertSee('class="error-toast no-print"', false)
        ->assertSee('The supplier id field is required.')
        ->assertDontSee('class="success-toast no-print"', false);
    expect(substr_count($response->getContent(), 'The supplier id field is required.'))->toBe(1);
    $this->assertDatabaseCount('products', 0);
    $this->assertDatabaseCount('inventories', 0);
    $this->assertDatabaseCount('product_units', 0);
    $this->assertDatabaseCount('activity_logs', 0);
});

test('all validation details and flashed errors share one popup without repeated messages', function (string $routeName) {
    $owner = createErrorPopupOwner();
    $errors = (new ViewErrorBag)->put('default', new MessageBag([
        'supplier_id' => ['Choose a supplier.'], 'product_name' => ['Enter a product name.'],
    ]));

    $response = $this->actingAs($owner)->withSession(['error' => 'Choose a supplier.', 'errors' => $errors])->get(route($routeName));

    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    expect($xpath->query('//div[@data-error-toast]')->length)->toBe(1);
    $messages = $xpath->query('//div[@data-error-toast]//li');
    expect($messages->length)->toBe(2);
    expect(trim($messages->item(0)->textContent))->toBe('Choose a supplier.');
    expect(trim($messages->item(1)->textContent))->toBe('Enter a product name.');
    expect(substr_count($response->getContent(), 'Choose a supplier.'))->toBe(1);
    expect(substr_count($response->getContent(), 'Enter a product name.'))->toBe(1);
})->with(['inventory.index', 'purchases.index', 'purchases.create', 'sales.create', 'sales.index']);

test('error popup messages cannot inject HTML or scripts', function () {
    $owner = createErrorPopupOwner();
    $message = '<script>alert("error")</script>';
    $validationMessage = '<img src=x onerror=alert(1)>';
    $errors = (new ViewErrorBag)->put('default', new MessageBag(['name' => [$validationMessage]]));

    $this->actingAs($owner)->withSession(['error' => $message, 'errors' => $errors])->get(route('products.index'))
        ->assertSee($message)->assertDontSee($message, false)
        ->assertSee($validationMessage)->assertDontSee($validationMessage, false);
});

test('pages without errors do not show an error popup', function () {
    $owner = createErrorPopupOwner();

    $this->actingAs($owner)->get(route('inventory.index'))->assertDontSee('class="error-toast no-print"', false);
});
