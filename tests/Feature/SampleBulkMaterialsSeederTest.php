<?php

use App\Models\Product;
use App\Models\SaleItem;
use App\Models\User;
use Database\Seeders\RealInventoryReplacementSeeder;
use Database\Seeders\SampleBulkMaterialsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['services.online_backup.path' => null]);
});

test('sample materials have explicit base units, package conversions and ten percent prices', function (string $name, float $stock, string $baseName, string $type, float $baseCost, float $basePrice, string $packageName, float $factor, float $packageCost, float $packagePrice) {
    $this->seed(SampleBulkMaterialsSeeder::class);

    $product = Product::with(['inventory', 'productUnits.unit', 'suppliers'])->where('product_name', $name)->firstOrFail();
    expect($product->description)->toContain('SAMPLE DATA');
    expect($product->suppliers->sole()->supplier_name)->toBe('Sample Supplier (Replace Before Use)');
    expect((float) $product->inventory->quantity_on_hand)->toBe($stock);
    expect($product->productUnits)->toHaveCount(2);
    $base = $product->productUnits->firstWhere('is_base_unit', true);
    expect($base->unit->unit_name)->toBe($baseName);
    expect($base->unit->unit_type)->toBe($type);
    expect((float) $base->conversion_factor)->toBe(1.0);
    expect((float) $base->purchase_cost)->toBe($baseCost);
    expect((float) $base->selling_price)->toBe($basePrice);
    $package = $product->productUnits->firstWhere('is_base_unit', false);
    expect($package->unit->unit_name)->toBe($packageName);
    expect($package->unit->unit_type)->toBe('COUNT');
    expect((float) $package->conversion_factor)->toBe($factor);
    expect((float) $package->purchase_cost)->toBe($packageCost);
    expect((float) $package->selling_price)->toBe($packagePrice);
})->with([
    'nails by weight' => ['Common Nail (Sample) — 2″', 50.0, 'Kilogram', 'WEIGHT', 150.0, 165.0, 'Box', 5.0, 750.0, 825.0],
    'sand by volume' => ['Sand (Sample)', 10.0, 'Cubic Meter', 'VOLUME', 900.0, 990.0, 'Bag', 0.025, 22.50, 24.75],
    'cement by weight' => ['Portland Cement (Sample)', 2000.0, 'Kilogram', 'WEIGHT', 7.50, 8.25, 'Bag', 40.0, 300.0, 330.0],
]);

test('adding samples preserves the complete real catalog and never resets edited sample stock or prices', function () {
    $this->seed(RealInventoryReplacementSeeder::class);
    $realTables = ['products', 'product_units', 'inventories', 'product_supplier', 'suppliers', 'categories', 'units_of_measure'];
    $originalRows = [];
    foreach ($realTables as $table) {
        $originalRows[$table] = DB::table($table)->get();
    }
    $owner = User::forceCreate(['username' => 'owner', 'password_hash' => Hash::make('secret'), 'role' => 'OWNER', 'is_active' => true]);
    $ownerAttributes = $owner->fresh()->getAttributes();
    $this->seed(SampleBulkMaterialsSeeder::class);
    $cement = Product::where('product_name', 'Portland Cement (Sample)')->firstOrFail();
    $cement->inventory()->update(['quantity_on_hand' => 1600]);
    $cement->productUnits()->where('is_base_unit', true)->update(['selling_price' => 9]);

    $this->seed(SampleBulkMaterialsSeeder::class);

    foreach ($originalRows as $table => $rows) {
        foreach ($rows as $row) {
            $this->assertDatabaseHas($table, (array) $row);
        }
    }
    $this->assertDatabaseCount('products', 40);
    $this->assertDatabaseCount('suppliers', 8);
    $this->assertDatabaseCount('product_units', 43);
    $this->assertDatabaseCount('units_of_measure', 9);
    $this->assertDatabaseCount('inventories', 40);
    $this->assertDatabaseCount('sales', 0);
    $this->assertDatabaseCount('purchases', 0);
    expect($owner->fresh()->getAttributes())->toBe($ownerAttributes);
    $this->assertDatabaseHas('inventories', ['product_id' => $cement->product_id, 'quantity_on_hand' => 1600]);
    $this->assertDatabaseHas('product_units', ['product_id' => $cement->product_id, 'is_base_unit' => true, 'selling_price' => 9]);
});

test('selling a sample package displays the package and deducts only converted base stock', function (string $name, string $packageName, float $remaining, float $baseQuantity, float $total) {
    $owner = User::forceCreate(['username' => 'owner', 'password_hash' => Hash::make('secret'), 'role' => 'OWNER', 'is_active' => true]);
    $this->seed(SampleBulkMaterialsSeeder::class);
    $product = Product::where('product_name', $name)->firstOrFail();
    $package = $product->productUnits()->where('is_base_unit', false)->firstOrFail();

    $this->actingAs($owner)->post(route('sales.store'), [
        'items' => [['product_unit_id' => $package->product_unit_id, 'quantity' => 1]],
        'payment' => 1000,
    ])->assertSessionHasNoErrors();

    $item = SaleItem::sole();
    expect($item->sellingUnitName())->toBe($packageName);
    expect($item->sellingQuantity())->toBe(1.0);
    expect($item->baseStockQuantity())->toBe($baseQuantity);
    expect((float) $item->subtotal)->toBe($total);
    $this->assertDatabaseHas('inventories', ['product_id' => $product->product_id, 'quantity_on_hand' => $remaining]);
})->with([
    'nail box deducts five kilograms' => ['Common Nail (Sample) — 2″', 'Box', 45.0, 5.0, 825.0],
    'sand bag deducts measured volume' => ['Sand (Sample)', 'Bag', 9.975, 0.025, 24.75],
    'cement bag deducts forty kilograms' => ['Portland Cement (Sample)', 'Bag', 1960.0, 40.0, 330.0],
]);

test('an interrupted sample import leaves no partial products or units', function () {
    $eventName = 'eloquent.creating: '.Product::class;
    Event::listen($eventName, function (Product $product): void {
        if ($product->product_name === 'Sand (Sample)') {
            throw new RuntimeException('Simulated sample import failure');
        }
    });

    try {
        expect(fn () => $this->seed(SampleBulkMaterialsSeeder::class))->toThrow(RuntimeException::class, 'Simulated sample import failure');
    } finally {
        Event::forget($eventName);
    }

    foreach (['products', 'product_units', 'inventories', 'suppliers', 'product_supplier', 'categories', 'units_of_measure'] as $table) {
        $this->assertDatabaseCount($table, 0);
    }
});
