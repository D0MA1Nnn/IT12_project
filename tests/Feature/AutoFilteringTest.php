<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

function createAutoFilteringOwner(): User
{
    return User::forceCreate([
        'username' => 'filter-owner',
        'password_hash' => Hash::make('password'),
        'first_name' => 'Filter',
        'last_name' => 'Owner',
        'role' => 'OWNER',
        'is_active' => true,
    ]);
}

test('list filters apply automatically without a filter button', function (string $routeName) {
    $owner = createAutoFilteringOwner();

    $response = $this->actingAs($owner)->get(route($routeName));

    $response->assertSee('data-auto-filter', false)
        ->assertSee('data-auto-filter-script', false);

    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);

    expect($xpath->query('//button[normalize-space(.)="Filter"]')->length)->toBe(0);
    expect($xpath->query('//form[@data-auto-filter and translate(@method,"GET","get")!="get"]')->length)->toBe(0);
})->with([
    'activity logs' => ['activity.index'],
    'categories' => ['categories.index'],
    'inventory' => ['inventory.index'],
    'products' => ['products.index'],
    'purchases' => ['purchases.index'],
    'suppliers' => ['suppliers.index'],
    'deliveries' => ['sales.index'],
    'sales report' => ['sales.report'],
]);

test('cashiering keeps its search and category controls without redundant help text', function () {
    $owner = createAutoFilteringOwner();

    $response = $this->actingAs($owner)->get(route('sales.create'));

    $response->assertSee('id="productSearch"', false)
        ->assertSee('id="categoryFilter"', false)
        ->assertSee('id="paymentMethod"', false)
        ->assertSee('id="delivery"', false)
        ->assertDontSee('filterButton', false)
        ->assertDontSee('codPaymentNote', false)
        ->assertDontSee('cashier-delivery-help', false)
        ->assertDontSee('No payment is collected now.')
        ->assertDontSee('You can immediately serve the next customer.');
});

test('search and status filters return only matching categories', function () {
    $owner = createAutoFilteringOwner();
    $matchingCategory = Category::create(['category_name' => 'Cement', 'is_active' => true]);
    Category::create(['category_name' => 'Cement archived', 'is_active' => false]);
    Category::create(['category_name' => 'Lumber', 'is_active' => true]);

    $response = $this->actingAs($owner)->get(route('categories.index', [
        'search' => 'Cement',
        'status' => 'active',
    ]));

    $response->assertViewHas('categories', fn ($categories): bool => $categories->total() === 1
        && $categories->currentPage() === 1
        && $categories->first()->is($matchingCategory)
    )->assertSee('Clear');
});
