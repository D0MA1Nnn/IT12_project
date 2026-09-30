<?php

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Supplier;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('an existing product unit can be promoted to the base unit', function () {
    $this->artisan('migrate:fresh', [
        '--no-interaction' => true,
    ]);

    $owner = User::forceCreate([
        'username' => 'owner',
        'password_hash' => Hash::make('owner123'),
        'first_name' => 'Owner',
        'last_name' => 'User',
        'role' => 'OWNER',
        'is_active' => true,
    ]);

    $category = Category::create([
        'category_name' => 'Construction Materials',
        'is_active' => true,
    ]);

    $supplier = Supplier::create([
        'supplier_name' => 'Davao Building Materials Supply',
        'contact_person' => 'Test',
        'contact_number' => '09171234567',
        'email' => 'test@example.com',
        'address' => 'Davao City',
        'is_active' => true,
    ]);

    $bag = UnitOfMeasure::create([
        'unit_name' => 'Bag',
        'unit_symbol' => 'bag',
        'unit_type' => 'Count',
        'is_active' => true,
    ]);

    $kilogram = UnitOfMeasure::create([
        'unit_name' => 'Kilogram',
        'unit_symbol' => 'kg',
        'unit_type' => 'Weight',
        'is_active' => true,
    ]);

    $product = Product::create([
        'category_id' => $category->category_id,
        'product_name' => 'Portland Cement',
        'description' => 'General purpose cement.',
        'is_active' => true,
    ]);

    $product->suppliers()->sync([$supplier->supplier_id]);

    Inventory::create([
        'product_id' => $product->product_id,
        'quantity_on_hand' => 186,
        'reorder_level' => 20,
        'last_updated' => now(),
    ]);

    ProductUnit::create([
        'product_id' => $product->product_id,
        'unit_id' => $bag->unit_id,
        'selling_price' => 280,
        'purchase_cost' => 245,
        'conversion_factor' => 1,
        'is_base_unit' => true,
        'is_active' => true,
    ]);

    ProductUnit::create([
        'product_id' => $product->product_id,
        'unit_id' => $kilogram->unit_id,
        'selling_price' => 8,
        'purchase_cost' => 7,
        'conversion_factor' => 0.025,
        'is_base_unit' => false,
        'is_active' => true,
    ]);

    $this->actingAs($owner)
        ->put(route('products.update', $product), [
            'category_id' => $category->category_id,
            'product_name' => 'Portland Cement',
            'supplier_id' => $supplier->supplier_id,
            'description' => 'General purpose cement.',
            'unit_id' => $kilogram->unit_id,
            'selling_price' => 8,
            'purchase_cost' => 7,
            'reorder_level' => 20,
        ])
        ->assertRedirect(route('products.index'));

    $kilogramProductUnit = ProductUnit::where('product_id', $product->product_id)
        ->where('unit_id', $kilogram->unit_id)
        ->firstOrFail();

    $bagProductUnit = ProductUnit::where('product_id', $product->product_id)
        ->where('unit_id', $bag->unit_id)
        ->firstOrFail();

    $inventory = Inventory::where('product_id', $product->product_id)
        ->firstOrFail();

    expect($kilogramProductUnit->is_base_unit)->toBeTrue()
        ->and((float) $kilogramProductUnit->conversion_factor)->toBe(1.0)
        ->and($bagProductUnit->is_base_unit)->toBeFalse()
        ->and((float) $bagProductUnit->conversion_factor)->toBe(40.0)
        ->and((float) $inventory->quantity_on_hand)->toBe(7440.0)
        ->and((float) $inventory->reorder_level)->toBe(800.0);
})->skip(
    ! in_array('sqlite', PDO::getAvailableDrivers(), true),
    'SQLite PDO driver is not installed in this PHP environment.'
);
