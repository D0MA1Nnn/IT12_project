<?php

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Services\DatabaseBackupService;
use Database\Seeders\RealInventoryReplacementSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['services.online_backup.path' => null]);
});

/** @return array{owner: User, product: Product, unitId: int} */
function createOldBusinessData(): array
{
    $owner = User::forceCreate(['username' => 'existing-owner', 'password_hash' => Hash::make('secret'), 'role' => 'OWNER', 'is_active' => true]);
    User::forceCreate(['username' => 'existing-clerk', 'password_hash' => Hash::make('clerk-secret'), 'role' => 'SALES_CLERK', 'is_active' => true]);
    $category = Category::create(['category_name' => 'Old Category', 'is_active' => true]);
    $supplier = Supplier::create(['supplier_name' => 'Old Supplier', 'is_active' => true]);
    $unit = UnitOfMeasure::create(['unit_name' => 'Old Unit', 'unit_symbol' => 'old', 'unit_type' => 'COUNT', 'is_active' => true]);
    $product = Product::create(['product_name' => 'Old Product', 'category_id' => $category->category_id, 'is_active' => true]);
    $product->inventory()->create(['quantity_on_hand' => 9, 'reorder_level' => 1]);
    $product->suppliers()->attach($supplier);
    $productUnit = $product->productUnits()->create(['unit_id' => $unit->unit_id, 'selling_price' => 20, 'purchase_cost' => 10, 'conversion_factor' => 1, 'is_base_unit' => true, 'is_active' => true]);
    $sale = Sale::create(['user_id' => $owner->user_id, 'sale_date' => now(), 'total_amount' => 20, 'status' => 'COMPLETED']);
    $sale->items()->create(['product_unit_id' => $productUnit->product_unit_id, 'quantity' => 1, 'unit_price' => 20, 'subtotal' => 20]);
    $purchase = Purchase::create(['supplier_id' => $supplier->supplier_id, 'user_id' => $owner->user_id, 'purchase_date' => now(), 'total_amount' => 100, 'status' => 'COMPLETED']);
    $purchase->items()->create(['product_unit_id' => $productUnit->product_unit_id, 'quantity' => 10, 'unit_cost' => 10, 'subtotal' => 100]);
    ActivityLog::create(['user_id' => $owner->user_id, 'module' => 'SALE', 'action' => 'CREATE', 'description' => 'Old test transaction']);

    return ['owner' => $owner, 'product' => $product, 'unitId' => $productUnit->product_unit_id];
}

test('replacement removes old business history, keeps accounts unchanged and creates no backup', function () {
    $fixtures = createOldBusinessData();
    $accounts = DB::table('users')->orderBy('user_id')->get()->toJson();
    $this->mock(DatabaseBackupService::class)->shouldNotReceive('createBackupFile', 'saveLatestOnlineBackup');

    $this->seed(RealInventoryReplacementSeeder::class);

    expect(DB::table('users')->orderBy('user_id')->get()->toJson())->toBe($accounts);
    $this->assertDatabaseMissing('products', ['product_id' => $fixtures['product']->product_id]);
    $this->assertDatabaseMissing('suppliers', ['supplier_name' => 'Old Supplier']);
    $this->assertDatabaseMissing('categories', ['category_name' => 'Old Category']);
    $this->assertDatabaseMissing('units_of_measure', ['unit_name' => 'Old Unit']);
    foreach (['sales', 'sale_items', 'purchases', 'purchase_items', 'activity_logs'] as $table) {
        $this->assertDatabaseCount($table, 0);
    }
    foreach (['products', 'product_units', 'inventories', 'product_supplier'] as $table) {
        $this->assertDatabaseCount($table, 37);
    }
    $this->assertDatabaseCount('suppliers', 7);
    expect(Supplier::whereNotNull('contact_number')->count())->toBe(0);
});

test('all approved entries retain their exact stock, purchase cost and rounded ten percent markup', function () {
    $this->seed(RealInventoryReplacementSeeder::class);

    $expected = [
        ['Yestar Hose Clamp — #1', 4, 'Pack', 27.90, 30.69],
        ['Yestar Double Hole Metal Pipe Clamp — #3 (20 mm)', 30, 'Piece', 10.70, 11.77],
        ['Yestar Double Hole Metal Pipe Clamp — #4', 30, 'Piece', 15.30, 16.83],
        ['Tile Adhesive', 10, 'Bag', 370, 407],
        ['PPR Coupling', 100, 'Piece', 4.20, 4.62],
        ['Safety Breaker — 20 A', 10, 'Piece', 345, 379.50],
        ['Safety Breaker — 30 A', 10, 'Piece', 345, 379.50],
        ['Safety Breaker with Metal Cover', 10, 'Piece', 506.25, 556.88],
        ['Switch — 1 Gang', 10, 'Piece', 59.25, 65.18],
        ['Outlet with Ground — 2 Gang', 10, 'Piece', 123.75, 136.13],
        ['Hinge — #2', 30, 'Piece', 47.20, 51.92],
        ['Hinge — #3', 50, 'Piece', 74.50, 81.95],
        ['PVC Pipe — #6 / 160 mm', 6, 'Length', 918, 1009.80],
        ['PVC Pipe — #8 / 200 mm', 4, 'Length', 1407.60, 1548.36],
        ['Tie Box — 800 g', 36, 'Piece', 58, 63.80],
        ['Lever Handle', 10, 'Piece', 155, 170.50],
        ['Stanley Hinge — 3×3', 50, 'Piece', 40, 44],
        ['Stanley Hinge — 3.5×3.5', 30, 'Piece', 48, 52.80],
        ['Stanley Hinge — 4×4', 30, 'Piece', 55, 60.50],
        ['Tex Metal Screw — 12×55', 3000, 'Piece', 0.68, 0.75],
        ['Tex Screw Adaptor — 45 mm', 5, 'Pack', 80, 88],
        ['PVC Tech Elbow — 4″×90°, DH', 100, 'Piece', 34.51, 37.96],
        ['PVC Tech Elbow — 2″×90°, DH', 100, 'Piece', 11.50, 12.65],
        ['PVC Tech Elbow — 4″×45°, DH', 60, 'Piece', 26.96, 29.66],
        ['Sani-Tech Pipe — 4″×3 m, with Hub', 30, 'Length', 306, 336.60],
        ['PE Tech TRI Straight Coupler — 20×20 mm', 100, 'Piece', 27.50, 30.25],
        ['PVC Faucet — P/B, Blue', 50, 'Piece', 16, 17.60],
        ['PVC Faucet — H/B, Blue', 50, 'Piece', 17, 18.70],
        ['Yestar LPG Regulator with Gauge', 10, 'Piece', 357.60, 393.36],
        ['GI Double Clamp — ½″ (4×2)', 100, 'Piece', 2.36, 2.60],
        ['Brass Ball Valve — ½″ (120)', 24, 'Piece', 120, 132],
        ['Superthin Cutting Wheel — 105×1.0×16 mm', 550, 'Piece', 11, 12.10],
        ['Stainless Duplex Strainer — 2½″', 30, 'Piece', 80, 88],
        ['Amak Electrical Butyl-Rubber Tape', 10, 'Roll', 101, 111.10],
        ['Putty Knife Blade without Handle — 4″', 12, 'Piece', 5.75, 6.33],
        ['Bosny — #183, Gray', 12, 'Can', 144.50, 158.95],
        ['Flat Cord Wire — #16/2, 1.25 mm²×150 m', 2, 'Roll', 3045.75, 3350.33],
    ];
    $products = Product::with(['inventory', 'productUnits.unit', 'suppliers'])->get()->keyBy('product_name');
    expect($products)->toHaveCount(37);
    foreach ($expected as [$name, $quantity, $unitName, $cost, $price]) {
        $product = $products->get($name);
        expect($product)->not->toBeNull();
        expect((float) $product->inventory->quantity_on_hand)->toBe((float) $quantity);
        expect($product->productUnits)->toHaveCount(1);
        $unit = $product->productUnits->first();
        expect((float) $unit->purchase_cost)->toBe((float) $cost);
        expect((float) $unit->selling_price)->toBe((float) $price);
        expect($unit->unit->unit_name)->toBe($unitName);
        expect($unit->is_base_unit)->toBeTrue();
        expect((float) $unit->conversion_factor)->toBe(1.0);
        expect($product->suppliers)->toHaveCount(1);
    }
    expect($products->get('PPR Coupling')->suppliers->first()->supplier_name)->toBe('Techno Trade Resources Inc.');
    expect(Product::where('product_name', 'like', '%Holcim%')->count())->toBe(0);
    expect(Product::where('product_name', 'like', '%Non-sag%')->count())->toBe(0);
});

test('replacement rolls back all deletions and partial inserts when import fails', function () {
    $fixtures = createOldBusinessData();
    $eventName = 'eloquent.creating: '.Product::class;
    Event::listen($eventName, function (Product $product): void {
        if ($product->group_name === 'Safety Breaker') {
            throw new RuntimeException('Simulated import failure');
        }
    });

    try {
        expect(fn () => $this->seed(RealInventoryReplacementSeeder::class))->toThrow(RuntimeException::class, 'Simulated import failure');
    } finally {
        Event::forget($eventName);
    }

    $this->assertModelExists($fixtures['product']);
    $this->assertDatabaseCount('products', 1);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 9]);
    $this->assertDatabaseCount('suppliers', 1);
    $this->assertDatabaseCount('sales', 1);
    $this->assertDatabaseCount('sale_items', 1);
    $this->assertDatabaseCount('purchases', 1);
    $this->assertDatabaseCount('purchase_items', 1);
    $this->assertDatabaseCount('activity_logs', 1);
    $this->assertDatabaseCount('users', 2);
});

test('imported sizes appear together in cashiering without inventing bag or pack conversions', function () {
    $fixtures = createOldBusinessData();
    $this->seed(RealInventoryReplacementSeeder::class);

    $this->actingAs($fixtures['owner'])->get(route('sales.create'))
        ->assertViewHas('products', fn ($products): bool => $products->count() === 37)
        ->assertViewHas('productGroups', fn ($groups): bool => $groups->contains(fn ($sizes): bool => $sizes->first()->groupLabel() === 'PVC Tech Elbow' && $sizes->count() === 3));

    $this->assertDatabaseCount('product_units', 37);
    expect(UnitOfMeasure::where('unit_name', 'Kilogram')->count())->toBe(0);
    expect(UnitOfMeasure::where('unit_name', 'Meter')->count())->toBe(0);
});

test('a cart containing a removed product cannot sell any new inventory', function () {
    $fixtures = createOldBusinessData();
    $this->seed(RealInventoryReplacementSeeder::class);

    $this->actingAs($fixtures['owner'])->post(route('sales.store'), [
        'items' => [['product_unit_id' => $fixtures['unitId'], 'quantity' => 1]], 'payment' => 1000,
    ])->assertSessionHasErrors('items.0.product_unit_id');

    $this->assertDatabaseCount('sales', 0);
    $this->assertDatabaseCount('sale_items', 0);
    $this->assertDatabaseCount('activity_logs', 0);
    $this->assertDatabaseHas('inventories', ['product_id' => Product::where('product_name', 'Tile Adhesive')->value('product_id'), 'quantity_on_hand' => 10]);
});

test('an explicit repeat replacement does not duplicate the catalog or overwrite accounts', function () {
    createOldBusinessData();
    $accounts = DB::table('users')->orderBy('user_id')->get()->toJson();
    $this->seed(RealInventoryReplacementSeeder::class);

    $this->seed(RealInventoryReplacementSeeder::class);

    $this->assertDatabaseCount('products', 37);
    $this->assertDatabaseCount('suppliers', 7);
    $this->assertDatabaseCount('inventories', 37);
    expect(DB::table('users')->orderBy('user_id')->get()->toJson())->toBe($accounts);
});
