<?php

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['services.online_backup.path' => null]);
});

function reportListOwner(): User
{
    return User::forceCreate([
        'username' => 'report-owner', 'password_hash' => Hash::make('secret'),
        'role' => 'OWNER', 'is_active' => true,
    ]);
}

/** @return array{owner: User, supplier: Supplier, product: Product, sales: array<int, Sale>, purchases: array<int, Purchase>} */
function reportListFixtures(): array
{
    $owner = reportListOwner();
    $supplier = Supplier::create(['supplier_name' => 'Report Supplier', 'is_active' => true]);
    $category = Category::create(['category_name' => 'Materials', 'is_active' => true]);
    $product = Product::create(['product_name' => 'Report Cement', 'category_id' => $category->category_id, 'is_active' => true]);
    $unit = UnitOfMeasure::create(['unit_name' => 'Bag', 'unit_symbol' => 'bag', 'unit_type' => 'COUNT', 'is_active' => true]);
    $productUnit = ProductUnit::create([
        'product_id' => $product->product_id, 'unit_id' => $unit->unit_id,
        'conversion_factor' => 1, 'is_base_unit' => true, 'is_active' => true,
        'selling_price' => 100, 'purchase_cost' => 80,
    ]);
    Inventory::create(['product_id' => $product->product_id, 'quantity_on_hand' => 80, 'reorder_level' => 5]);
    $sales = [];
    $purchases = [];
    foreach ([['2026-09-01 00:00:00', 100], ['2026-09-05 23:59:59', 200], ['2026-09-10 12:00:00', 300]] as [$date, $amount]) {
        $sale = Sale::create([
            'user_id' => $owner->user_id, 'sale_date' => $date, 'total_amount' => $amount,
            'status' => 'COMPLETED', 'delivery_required' => $amount === 200,
            'delivery_status' => $amount === 200 ? 'DELIVERED' : 'NOT_REQUIRED',
            'payment_method' => 'PAY_NOW', 'payment_status' => 'PAID', 'payment_received' => $amount,
        ]);
        SaleItem::create(['sale_id' => $sale->sale_id, 'product_unit_id' => $productUnit->product_unit_id, 'quantity' => 2, 'unit_price' => $amount / 2, 'subtotal' => $amount]);
        $sales[] = $sale;
        $purchase = Purchase::create([
            'supplier_id' => $supplier->supplier_id, 'user_id' => $owner->user_id,
            'purchase_date' => $date, 'total_amount' => $amount * 4, 'status' => 'COMPLETED',
        ]);
        PurchaseItem::create(['purchase_id' => $purchase->purchase_id, 'product_unit_id' => $productUnit->product_unit_id, 'quantity' => 2, 'unit_cost' => $amount * 2, 'subtotal' => $amount * 4]);
        $purchases[] = $purchase;
    }
    foreach (['PENDING', 'CANCELLED'] as $status) {
        Sale::create([
            'user_id' => $owner->user_id, 'sale_date' => '2026-09-05 12:00:00', 'total_amount' => 900,
            'status' => $status, 'delivery_required' => true, 'delivery_status' => $status,
            'payment_method' => 'COD', 'payment_status' => 'UNPAID', 'payment_received' => 0,
        ]);
        Purchase::create([
            'supplier_id' => $supplier->supplier_id, 'user_id' => $owner->user_id,
            'purchase_date' => '2026-09-05 12:00:00', 'total_amount' => 900, 'status' => $status,
        ]);
    }

    return compact('owner', 'supplier', 'product', 'sales', 'purchases');
}

test('Report defaults to completed sales and gives owners both list options', function () {
    $fixtures = reportListFixtures();

    $response = $this->actingAs($fixtures['owner'])->get(route('sales.report'));

    $response->assertSee('name="report_type"', false)->assertSee('data-auto-filter', false)
        ->assertSee('value="purchases"', false)->assertSee('Sales List')->assertSee('Purchases List')
        ->assertDontSee('Sales Report')->assertSee('Report — Sales List')
        ->assertSee('Completed Sales')->assertSee('Total Sales Amount')->assertSee('Average Sale')
        ->assertSee('<th class="no-print">Action</th>', false)->assertSee('Sale Details')->assertDontSee('PUR-', false)
        ->assertViewHas('reportType', 'sales')->assertViewHas('totalRecords', 3)
        ->assertViewHas('totalAmount', 600.0)->assertViewHas('averageAmount', 200)
        ->assertViewHas('walkInSales', 2)->assertViewHas('deliverySales', 1);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 80]);
    $this->assertDatabaseCount('activity_logs', 0);
});

test('Purchases List shows purchase-specific columns totals and print heading without sales', function () {
    $fixtures = reportListFixtures();

    $response = $this->actingAs($fixtures['owner'])->get(route('sales.report', ['report_type' => 'purchases']));

    $response->assertSee('Report — Purchases List')->assertSee('Completed Purchases')
        ->assertSee('Total Purchase Amount')->assertSee('Average Purchase')->assertSee('Report Supplier')
        ->assertSee('<th>Supplier</th>', false)->assertSee('<th>Status</th>', false)
        ->assertDontSee('<th>Delivery</th>', false)->assertDontSee('View Invoice')->assertDontSee('SALE-', false)
        ->assertSee('print=1', false)->assertSee('Purchase Details')->assertSee('₱2,400.00')
        ->assertViewHas('reportType', 'purchases')->assertViewHas('totalRecords', 3)
        ->assertViewHas('totalAmount', 2400.0)->assertViewHas('averageAmount', 800);
    $this->assertDatabaseCount('purchases', 5);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 80]);
});

test('search and date filters constrain the selected list and its summary', function (string $type, array $filters, int $count, float $amount) {
    $this->travelTo(Carbon::parse('2026-10-10 12:00:00'));
    $fixtures = reportListFixtures();

    $response = $this->actingAs($fixtures['owner'])->get(route('sales.report', ['report_type' => $type, ...$filters]));

    $response->assertSessionHasNoErrors()->assertViewHas('totalRecords', $count)
        ->assertViewHas('totalAmount', $amount)
        ->assertViewHas('records', fn ($records): bool => $records->total() === $count)
        ->assertDontSee('>Clear</a>', false);
})->with([
    'sales inclusive range' => ['sales', ['from' => '2026-09-05', 'to' => '2026-09-05'], 1, 200.0],
    'purchases inclusive range' => ['purchases', ['from' => '2026-09-05', 'to' => '2026-09-05'], 1, 800.0],
    'sales start only' => ['sales', ['from' => '2026-09-05'], 2, 500.0],
    'purchases start only' => ['purchases', ['from' => '2026-09-05'], 2, 2000.0],
    'sales end only' => ['sales', ['to' => '2026-09-05'], 2, 300.0],
    'purchases end only' => ['purchases', ['to' => '2026-09-05'], 2, 1200.0],
    'sales recorder' => ['sales', ['q' => 'report-owner'], 3, 600.0],
    'purchase recorder' => ['purchases', ['q' => 'report-owner'], 3, 2400.0],
    'purchase supplier and date' => ['purchases', ['q' => 'Report Supplier', 'from' => '2026-09-05', 'to' => '2026-09-05'], 1, 800.0],
    'sales no matches' => ['sales', ['q' => 'Nobody'], 0, 0.0],
    'purchase no matches' => ['purchases', ['q' => 'Nobody'], 0, 0.0],
]);

test('report reference search uses the selected list identifier', function (string $type, string $prefix, float $amount) {
    $fixtures = reportListFixtures();
    $reference = $prefix.str_pad($fixtures[$type][1]->getKey(), 4, '0', STR_PAD_LEFT);

    $response = $this->actingAs($fixtures['owner'])->get(route('sales.report', ['report_type' => $type, 'q' => $reference]));

    $response->assertViewHas('totalRecords', 1)->assertViewHas('totalAmount', $amount)->assertSee($reference);
})->with(['sale' => ['sales', 'SALE-', 200.0], 'purchase' => ['purchases', 'PUR-', 800.0]]);

test('purchase report pagination retains list search and dates and summarizes all filtered records', function () {
    $this->travelTo(Carbon::parse('2026-10-10 12:00:00'));
    $owner = reportListOwner();
    $supplier = Supplier::create(['supplier_name' => 'Page Supplier', 'is_active' => true]);
    for ($index = 0; $index < 16; $index++) {
        Purchase::create(['user_id' => $owner->user_id, 'supplier_id' => $supplier->supplier_id, 'purchase_date' => '2026-09-05 12:00:00', 'total_amount' => 10, 'status' => 'COMPLETED']);
    }

    $response = $this->actingAs($owner)->get(route('sales.report', ['report_type' => 'purchases', 'q' => 'Page Supplier', 'from' => '2026-09-01', 'to' => '2026-09-10', 'page' => 2]));

    $response->assertViewHas('totalRecords', 16)->assertViewHas('totalAmount', 160.0)
        ->assertViewHas('records', fn ($records): bool => $records->currentPage() === 2 && $records->count() === 1);
    $records = $response->viewData('records');
    parse_str(parse_url($records->previousPageUrl(), PHP_URL_QUERY), $query);
    expect($query)->toBe(['report_type' => 'purchases', 'q' => 'Page Supplier', 'from' => '2026-09-01', 'to' => '2026-09-10', 'page' => '1']);
});

test('sales clerks retain Report sales access but cannot choose or request purchases', function () {
    $user = reportListOwner();
    $user->update(['role' => 'SALES_CLERK']);

    $response = $this->actingAs($user)->get(route('sales.report'));

    $response->assertSee('Sales List')->assertDontSee('value="purchases"', false)->assertDontSee('Sales Report');
    $this->get(route('sales.report', ['report_type' => 'purchases']))->assertForbidden();
});

test('guests cannot access either report list', function (string $type) {
    $this->get(route('sales.report', ['report_type' => $type]))->assertRedirect(route('login'));
})->with(['sales', 'purchases']);

test('Report rejects invalid list choices and date filters', function (array $filters, string $field, string $message) {
    $this->travelTo(Carbon::parse('2026-10-10 12:00:00'));
    $owner = reportListOwner();

    $this->actingAs($owner)->get(route('sales.report', $filters))->assertSessionHasErrors([$field => $message]);
})->with([
    'unknown list' => [['report_type' => 'users'], 'report_type', 'Please select Sales List or Purchases List.'],
    'SQL-like list' => [['report_type' => 'purchases; DROP TABLE sales'], 'report_type', 'Please select Sales List or Purchases List.'],
    'malformed purchase date' => [['report_type' => 'purchases', 'from' => 'invalid'], 'from', 'From date must be a valid date.'],
    'future purchase date' => [['report_type' => 'purchases', 'to' => '2026-10-11'], 'to', 'To date cannot be in the future.'],
    'reversed purchase range' => [['report_type' => 'purchases', 'from' => '2026-09-10', 'to' => '2026-09-01'], 'to', 'To date cannot be earlier than the From date.'],
]);

test('purchase report escapes supplier and recorder names', function () {
    $fixtures = reportListFixtures();
    $supplierName = '<script>alert(1)</script>';
    $recorderName = '<img src=x onerror=alert(2)>';
    $fixtures['supplier']->update(['supplier_name' => $supplierName]);
    $fixtures['owner']->update(['username' => $recorderName]);

    $response = $this->actingAs($fixtures['owner'])->get(route('sales.report', ['report_type' => 'purchases']));

    $response->assertSee($supplierName)->assertDontSee($supplierName, false)
        ->assertSee($recorderName)->assertDontSee($recorderName, false);
});

test('report toolbar puts search before the list selector and has no Clear button', function (string $type) {
    $this->travelTo(Carbon::parse('2026-10-10 12:00:00'));
    $fixtures = reportListFixtures();

    $response = $this->actingAs($fixtures['owner'])->get(route('sales.report', [
        'report_type' => $type, 'q' => 'report-owner', 'from' => '2026-09-01', 'to' => '2026-09-10', 'page' => 2,
    ]));

    $response->assertSeeInOrder(['name="q"', 'name="report_type"', 'name="from"', 'name="to"'], false)
        ->assertDontSee('>Clear</a>', false)
        ->assertSee('type="button" class="btn primary sales-report-print"', false)
        ->assertSee('data-report-print-script', false)
        ->assertDontSee('target="_blank"', false);
    preg_match('/data-report-print-url="([^"]+)"/', $response->getContent(), $matches);
    parse_str(parse_url(html_entity_decode($matches[1]), PHP_URL_QUERY), $query);
    expect($query)->toBe(['report_type' => $type, 'q' => 'report-owner', 'from' => '2026-09-01', 'to' => '2026-09-10', 'print' => '1']);
})->with(['sales', 'purchases']);

test('both report dates have visible calendar buttons associated with the correct input', function (string $type) {
    $owner = reportListOwner();

    $response = $this->actingAs($owner)->get(route('sales.report', ['report_type' => $type]));

    $response->assertSee('id="report-from"', false)->assertSee('id="report-to"', false)
        ->assertSee('data-report-date-picker="report-from" aria-label="Choose From date"', false)
        ->assertSee('data-report-date-picker="report-to" aria-label="Choose To date"', false)
        ->assertSee('data-report-calendar-script', false);
})->with(['sales', 'purchases']);

test('report View buttons open separate read-only details for each row', function (string $type) {
    $fixtures = reportListFixtures();
    $firstId = $fixtures[$type][0]->getKey();
    $lastId = $fixtures[$type][2]->getKey();

    $response = $this->actingAs($fixtures['owner'])->get(route('sales.report', ['report_type' => $type]));

    $response->assertSee('data-view-report="reportDetailsModal'.$firstId.'"', false)
        ->assertSee('data-view-report="reportDetailsModal'.$lastId.'"', false)
        ->assertSee('id="reportDetailsModal'.$firstId.'"', false)
        ->assertSee('id="reportDetailsModal'.$lastId.'"', false)
        ->assertSee('aria-modal="true" aria-hidden="true"', false)
        ->assertSee('Report Cement')->assertSee('Bag')->assertSee('Quantity')->assertSee('Subtotal')
        ->assertDontSee('Save Changes')
        ->assertViewHas('records', fn ($records): bool => $records->first()->relationLoaded('items')
            && $records->first()->items->first()->productUnit->relationLoaded('product'));
    preg_match('/<div class="report-modal-body">([\s\S]*?)<div class="report-modal-footer">/', $response->getContent(), $matches);
    expect($matches[1])->not->toContain('<form');
    $this->assertDatabaseCount('sales', 5);
    $this->assertDatabaseCount('purchases', 5);
    $this->assertDatabaseCount('activity_logs', 0);
})->with(['sales', 'purchases']);

test('printed purchases include stored item costs and supplier contacts within the selected filters', function () {
    $this->travelTo(Carbon::parse('2026-10-10 12:00:00'));
    $fixtures = reportListFixtures();
    $fixtures['supplier']->update(['contact_person' => 'Supplier Contact', 'contact_number' => '09123456789', 'email' => 'supplier@gmail.com', 'address' => 'Davao Supplier Address']);
    $fixtures['purchases'][1]->items->first()->productUnit->update(['purchase_cost' => 9999]);
    $excludedReference = 'PUR-'.str_pad($fixtures['purchases'][0]->getKey(), 4, '0', STR_PAD_LEFT);

    $response = $this->actingAs($fixtures['owner'])->get(route('sales.report', [
        'report_type' => 'purchases', 'print' => 1, 'q' => 'Report Supplier', 'from' => '2026-09-05', 'to' => '2026-09-05', 'page' => 99,
    ]));

    $response->assertViewIs('sales.report-print')->assertViewHas('totalRecords', 1)
        ->assertViewHas('totalAmount', 800.0)->assertSee('Report Cement')->assertSee('Bag')
        ->assertSee('Purchase Cost / Unit')->assertSee('₱400.00')->assertDontSee('₱9,999.00')
        ->assertSee('Supplier Contact')->assertSee('09123456789')->assertSee('supplier@gmail.com')
        ->assertSee('Davao Supplier Address')->assertSee('Search: Report Supplier')
        ->assertSee('2026-09-05 to 2026-09-05')->assertDontSee($excludedReference)
        ->assertDontSee('data-view-report', false)->assertDontSee('sales-report-pagination', false)
        ->assertSee('<body data-report-print-document>', false)
        ->assertDontSee('<button', false)->assertDontSee('window.print()', false);
});

test('sale details and printing retain the original selling unit prices payment and delivery information', function (int $print) {
    $fixtures = reportListFixtures();
    $sale = $fixtures['sales'][1];
    $sale->update([
        'total_amount' => 240, 'delivery_fee' => 40, 'payment_received' => 300,
        'customer_name' => 'Delivery Customer', 'customer_contact_number' => '09111111111',
        'delivery_address' => 'Davao Delivery Address',
    ]);
    $sale->items->first()->update(['selling_details' => ['quantity' => 0.5, 'unit' => 'Box', 'unit_price' => 400]]);
    $sale->items->first()->productUnit->update(['selling_price' => 9999]);

    $response = $this->actingAs($fixtures['owner'])->get(route('sales.report', [
        'q' => 'SALE-'.str_pad($sale->getKey(), 4, '0', STR_PAD_LEFT), 'print' => $print,
    ]));

    $response->assertSee('Report Cement')->assertSee('Box')->assertSee('>0.5</td>', false)
        ->assertSee('₱400.00')->assertSee('₱200.00')->assertDontSee('₱9,999.00')
        ->assertSee('Delivery Customer')->assertSee('09111111111')->assertSee('Davao Delivery Address')
        ->assertSee('Delivery Fee')->assertSee('₱40.00')->assertSee('₱240.00')
        ->assertSee('Payment Received')->assertSee('₱300.00')->assertSee('Change')->assertSee('₱60.00')
        ->assertSee('DELIVERED');
})->with(['popup' => 0, 'print' => 1]);

test('printed unpaid COD sales show the amount due without payment received or change', function () {
    $fixtures = reportListFixtures();
    $sale = $fixtures['sales'][1];
    $sale->update(['payment_method' => 'COD', 'payment_status' => 'UNPAID', 'payment_received' => 0, 'delivery_status' => 'PENDING']);

    $response = $this->actingAs($fixtures['owner'])->get(route('sales.report', ['print' => 1, 'q' => 'SALE-'.str_pad($sale->getKey(), 4, '0', STR_PAD_LEFT)]));

    $response->assertSee('Cash on Delivery (COD)')->assertSee('Amount Due on Delivery')->assertSee('₱200.00')
        ->assertDontSee('Payment Received')->assertDontSee('<dt>Change</dt>', false);
});

test('printing includes every completed record beyond pagination and chunk boundaries', function (string $type) {
    $fixtures = reportListFixtures();
    $model = $type === 'purchases' ? Purchase::class : Sale::class;
    $dateColumn = $type === 'purchases' ? 'purchase_date' : 'sale_date';
    $attributes = ['user_id' => $fixtures['owner']->user_id, $dateColumn => '2026-09-05 12:00:00', 'status' => 'COMPLETED', 'total_amount' => 10];
    if ($type === 'purchases') {
        $attributes['supplier_id'] = $fixtures['supplier']->supplier_id;
    }
    for ($index = 0; $index < 198; $index++) {
        $model::create($attributes);
    }

    $response = $this->actingAs($fixtures['owner'])->get(route('sales.report', ['report_type' => $type, 'print' => 1, 'page' => 99]));

    $response->assertViewIs('sales.report-print')->assertViewHas('totalRecords', 201)
        ->assertSee('No items found.')->assertDontSee('sales-report-pagination', false);
    expect(substr_count($response->getContent(), '<article class="report-transaction">'))->toBe(201);
    $this->assertDatabaseCount('activity_logs', 0);
    $this->assertDatabaseHas('inventories', ['product_id' => $fixtures['product']->product_id, 'quantity_on_hand' => 80]);
})->with(['sales', 'purchases']);

test('printing an empty filtered list keeps zero totals and its empty message', function (string $type) {
    $fixtures = reportListFixtures();

    $response = $this->actingAs($fixtures['owner'])->get(route('sales.report', ['report_type' => $type, 'print' => 1, 'q' => 'Nobody']));

    $response->assertSee('No '.$type.' found.')->assertSee('₱0.00')
        ->assertViewHas('totalRecords', 0)->assertViewHas('totalAmount', 0.0)->assertViewHas('averageAmount', 0);
})->with(['sales', 'purchases']);

test('print reports preserve authentication and owner-only purchase access', function () {
    $this->get(route('sales.report', ['print' => 1]))->assertRedirect(route('login'));
    $user = reportListOwner();
    $user->update(['role' => 'SALES_CLERK']);

    $this->actingAs($user)->get(route('sales.report', ['print' => 1]))->assertViewIs('sales.report-print');
    $this->get(route('sales.report', ['report_type' => 'purchases', 'print' => 1]))->assertForbidden();
});

test('Report rejects invalid print options', function () {
    $user = reportListOwner();

    $this->actingAs($user)->get(route('sales.report', ['print' => 'invalid']))
        ->assertSessionHasErrors(['print' => 'The print field must be true or false.']);
});

test('printed details escape product supplier customer contact and address text', function (string $type) {
    $fixtures = reportListFixtures();
    $malicious = '<img src=x onerror=alert(1)>';
    $fixtures['product']->update(['product_name' => $malicious]);
    $fixtures['supplier']->update(['supplier_name' => $malicious, 'contact_person' => $malicious, 'email' => $malicious, 'address' => $malicious]);
    $fixtures['sales'][1]->update(['customer_name' => $malicious, 'customer_contact_number' => $malicious, 'delivery_address' => $malicious]);

    $response = $this->actingAs($fixtures['owner'])->get(route('sales.report', ['report_type' => $type, 'print' => 1]));

    $response->assertSee($malicious)->assertDontSee($malicious, false);
})->with(['sales', 'purchases']);
