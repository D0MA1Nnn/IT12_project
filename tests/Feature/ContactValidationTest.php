<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\SampleBulkMaterialsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['services.online_backup.path' => null]);
});

/** @return array{owner: User, supplier: Supplier, product: Product} */
function contactValidationFixtures(): array
{
    $owner = User::forceCreate(['username' => 'owner', 'password_hash' => Hash::make('secret'), 'role' => 'OWNER', 'is_active' => true]);
    $category = Category::create(['category_name' => 'Hardware', 'is_active' => true]);
    $product = Product::create(['category_id' => $category->category_id, 'product_name' => 'Existing Product', 'is_active' => true]);
    $supplier = Supplier::create(['supplier_name' => 'Existing Supplier', 'contact_number' => '09123456789', 'is_active' => true]);
    $supplier->products()->attach($product);

    return compact('owner', 'supplier', 'product');
}

dataset('supplier contact actions', ['add' => ['post'], 'edit' => ['put']]);

/** @return array{supplier_name: string, contact_person: string, contact_number: string, email: string, address: string} */
function validSupplierContactDetails(): array
{
    return [
        'supplier_name' => 'Changed Supplier', 'contact_person' => 'Juan Dela Cruz',
        'contact_number' => '09123456789', 'email' => 'supplier@gmail.com', 'address' => 'Davao City',
    ];
}

dataset('invalid contact details', [
    'missing zero prefix' => ['contact_number', '98734739437', 'Contact number must start with 09 and contain exactly 11 digits.'],
    'wrong second digit' => ['contact_number', '08123456789', 'Contact number must start with 09 and contain exactly 11 digits.'],
    'too short' => ['contact_number', '0912345678', 'Contact number must start with 09 and contain exactly 11 digits.'],
    'too long' => ['contact_number', '091234567890', 'Contact number must start with 09 and contain exactly 11 digits.'],
    'international format' => ['contact_number', '+639123456789', 'Contact number must start with 09 and contain exactly 11 digits.'],
    'letters in phone' => ['contact_number', '0912345678a', 'Contact number must start with 09 and contain exactly 11 digits.'],
    'hyphen in phone' => ['contact_number', '09123-456789', 'Contact number must start with 09 and contain exactly 11 digits.'],
    'unicode digits in phone' => ['contact_number', '09１２３４５６７８９', 'Contact number must start with 09 and contain exactly 11 digits.'],
    'contact person beyond limit' => ['contact_person', str_repeat('ñ', 61), 'Contact person must not be longer than 60 characters.'],
    'supplier name beyond limit' => ['supplier_name', str_repeat('ñ', 61), 'Supplier name must not be longer than 60 characters.'],
    'email beyond limit' => ['email', str_repeat('a', 51).'@gmail.com', 'Email must not be longer than 60 characters.'],
    'invalid email syntax' => ['email', 'not-an-email', 'The email field must be a valid email address.'],
    'space in email' => ['email', 'Archive test@gmail.com', 'The email field must be a valid email address.'],
    'missing gmail ending' => ['email', 'TrustHardware@gmail', 'Email must end with @gmail.com, @yahoo.com, or @outlook.com.'],
    'missing yahoo ending' => ['email', 'supplier@yahoo', 'Email must end with @gmail.com, @yahoo.com, or @outlook.com.'],
    'missing outlook ending' => ['email', 'supplier@outlook', 'Email must end with @gmail.com, @yahoo.com, or @outlook.com.'],
    'misspelled gmail' => ['email', 'supplier@gamil.com', 'Email must end with @gmail.com, @yahoo.com, or @outlook.com.'],
    'different provider' => ['email', 'supplier@example.com', 'Email must end with @gmail.com, @yahoo.com, or @outlook.com.'],
    'subdomain' => ['email', 'supplier@mail.gmail.com', 'Email must end with @gmail.com, @yahoo.com, or @outlook.com.'],
    'extra domain suffix' => ['email', 'supplier@gmail.com.example.com', 'Email must end with @gmail.com, @yahoo.com, or @outlook.com.'],
    'different domain ending' => ['email', 'supplier@gmail.co', 'Email must end with @gmail.com, @yahoo.com, or @outlook.com.'],
    'address beyond limit' => ['address', str_repeat('ñ', 201), 'Address must not be longer than 200 characters.'],
]);

test('supplier add and edit reject invalid contacts without changing records or product links', function (string $method, string $field, string $value, string $message) {
    $fixtures = contactValidationFixtures();
    $supplier = $fixtures['supplier'];
    $original = $supplier->fresh()->getAttributes();
    $payload = array_merge(validSupplierContactDetails(), ['product_ids' => [], $field => $value]);
    $endpoint = $method === 'post' ? route('suppliers.store') : route('suppliers.update', $supplier);

    $this->actingAs($fixtures['owner'])->{$method}($endpoint, $payload)->assertSessionHasErrors([$field => $message]);

    $this->assertDatabaseCount('suppliers', 1);
    $this->assertDatabaseHas('suppliers', $original);
    $this->assertDatabaseHas('product_supplier', ['product_id' => $fixtures['product']->product_id, 'supplier_id' => $supplier->supplier_id]);
    $this->assertDatabaseCount('activity_logs', 0);
})->with('supplier contact actions')->with('invalid contact details');

test('supplier add and edit accept contacts at the maximum length and retain the phone leading zero', function (string $method) {
    $fixtures = contactValidationFixtures();
    $payload = [
        'supplier_name' => str_repeat('S', 60), 'contact_person' => str_repeat('ñ', 60),
        'contact_number' => '09999999999',
        'email' => str_repeat('a', 50).'@gmail.com',
        'address' => str_repeat('ñ', 200), 'product_ids' => [$fixtures['product']->product_id],
    ];
    $endpoint = $method === 'post' ? route('suppliers.store') : route('suppliers.update', $fixtures['supplier']);

    $this->actingAs($fixtures['owner'])->{$method}($endpoint, $payload)->assertSessionHasNoErrors()->assertRedirect(route('suppliers.index'));

    $this->assertDatabaseHas('suppliers', array_diff_key($payload, ['product_ids' => true]));
    $saved = Supplier::where('supplier_name', $payload['supplier_name'])->firstOrFail();
    $this->assertDatabaseHas('product_supplier', ['supplier_id' => $saved->supplier_id, 'product_id' => $fixtures['product']->product_id]);
    $this->assertDatabaseCount('activity_logs', 1);
})->with('supplier contact actions');

test('supplier add and edit accept all three email providers including uppercase domains', function (string $method, string $email) {
    $fixtures = contactValidationFixtures();
    $payload = array_merge(validSupplierContactDetails(), ['email' => $email]);
    $endpoint = $method === 'post' ? route('suppliers.store') : route('suppliers.update', $fixtures['supplier']);

    $this->actingAs($fixtures['owner'])->{$method}($endpoint, $payload)->assertSessionHasNoErrors()->assertRedirect(route('suppliers.index'));

    $this->assertDatabaseHas('suppliers', $payload);
    $this->assertDatabaseCount('activity_logs', 1);
})->with('supplier contact actions')->with([
    'gmail' => 'supplier@gmail.com', 'yahoo' => 'supplier@yahoo.com', 'outlook' => 'supplier@outlook.com',
    'uppercase gmail' => 'Supplier@GMAIL.COM', 'uppercase yahoo' => 'Supplier@YAHOO.COM', 'uppercase outlook' => 'Supplier@OUTLOOK.COM',
    'gmail plus tag' => 'supplier+orders@gmail.com',
]);

test('supplier add and edit reject blank details without changing records or links', function (string $method, ?string $blank) {
    $fixtures = contactValidationFixtures();
    $original = $fixtures['supplier']->fresh()->getAttributes();
    $endpoint = $method === 'post' ? route('suppliers.store') : route('suppliers.update', $fixtures['supplier']);

    $this->actingAs($fixtures['owner'])->{$method}($endpoint, array_fill_keys(array_keys(validSupplierContactDetails()), $blank))
        ->assertSessionHasErrors([
            'supplier_name' => 'Supplier name is required.', 'contact_person' => 'Contact person is required.',
            'contact_number' => 'Phone is required.', 'email' => 'Email is required.', 'address' => 'Address is required.',
        ])->assertSessionMissing('success');

    $this->assertDatabaseCount('suppliers', 1);
    $this->assertDatabaseHas('suppliers', $original);
    $this->assertDatabaseHas('product_supplier', ['product_id' => $fixtures['product']->product_id, 'supplier_id' => $fixtures['supplier']->supplier_id]);
    $this->assertDatabaseCount('activity_logs', 0);
})->with('supplier contact actions')->with(['empty' => [''], 'spaces only' => ['   '], 'null' => [null]]);

test('supplier add and edit require every detail even when other details are valid', function (string $method, string $field, string $message) {
    $fixtures = contactValidationFixtures();
    $original = $fixtures['supplier']->fresh()->getAttributes();
    $payload = validSupplierContactDetails();
    unset($payload[$field]);
    $endpoint = $method === 'post' ? route('suppliers.store') : route('suppliers.update', $fixtures['supplier']);

    $this->actingAs($fixtures['owner'])->{$method}($endpoint, $payload)->assertSessionHasErrors([$field => $message])->assertSessionMissing('success');

    $this->assertDatabaseCount('suppliers', 1);
    $this->assertDatabaseHas('suppliers', $original);
    $this->assertDatabaseHas('product_supplier', ['product_id' => $fixtures['product']->product_id, 'supplier_id' => $fixtures['supplier']->supplier_id]);
    $this->assertDatabaseCount('activity_logs', 0);
})->with('supplier contact actions')->with([
    'name' => ['supplier_name', 'Supplier name is required.'],
    'contact person' => ['contact_person', 'Contact person is required.'],
    'phone' => ['contact_number', 'Phone is required.'],
    'email' => ['email', 'Email is required.'],
    'address' => ['address', 'Address is required.'],
]);

test('complete supplier details can be saved without any products offered', function (string $method, array $products) {
    $fixtures = contactValidationFixtures();
    $payload = array_merge(validSupplierContactDetails(), $products);
    $endpoint = $method === 'post' ? route('suppliers.store') : route('suppliers.update', $fixtures['supplier']);

    $this->actingAs($fixtures['owner'])->{$method}($endpoint, $payload)->assertSessionHasNoErrors()->assertRedirect(route('suppliers.index'));

    $this->assertDatabaseHas('suppliers', validSupplierContactDetails());
    $saved = Supplier::where('supplier_name', 'Changed Supplier')->firstOrFail();
    $this->assertDatabaseMissing('product_supplier', ['supplier_id' => $saved->supplier_id]);
    $this->assertDatabaseCount('activity_logs', 1);
})->with('supplier contact actions')->with([
    'omitted' => [[]], 'empty selection' => [['product_ids' => []]], 'null selection' => [['product_ids' => null]],
]);

test('delivery phones also reject numbers without the required local format', function (string $phone) {
    $fixtures = contactValidationFixtures();
    $this->seed(SampleBulkMaterialsSeeder::class);
    $cement = Product::where('product_name', 'Portland Cement (Sample)')->firstOrFail();
    $unit = $cement->productUnits()->where('is_base_unit', true)->firstOrFail();

    $this->actingAs($fixtures['owner'])->post(route('sales.store'), [
        'items' => [['product_unit_id' => $unit->product_unit_id, 'quantity' => 1]], 'payment' => 100,
        'delivery_required' => 1, 'customer_name' => 'Customer', 'customer_contact_number' => $phone,
        'delivery_address' => 'Davao City', 'delivery_fee' => 0,
    ])->assertSessionHasErrors(['customer_contact_number' => 'Customer contact number must start with 09 and contain exactly 11 digits.']);

    $this->assertDatabaseCount('sales', 0);
    $this->assertDatabaseCount('sale_items', 0);
    $this->assertDatabaseCount('activity_logs', 0);
    $this->assertDatabaseHas('inventories', ['product_id' => $cement->product_id, 'quantity_on_hand' => 2000]);
})->with([
    'missing prefix' => '98734739437', 'wrong prefix' => '08123456789', 'short' => '0912345678',
    'long' => '091234567890', 'letters' => '0912345678a', 'international' => '+639123456789',
    'unicode digits' => '09１２３４５６７８９',
]);

test('delivery accepts an eleven digit 09 phone and existing customer length limits', function () {
    $fixtures = contactValidationFixtures();
    $this->seed(SampleBulkMaterialsSeeder::class);
    $cement = Product::where('product_name', 'Portland Cement (Sample)')->firstOrFail();
    $unit = $cement->productUnits()->where('is_base_unit', true)->firstOrFail();

    $this->actingAs($fixtures['owner'])->post(route('sales.store'), [
        'items' => [['product_unit_id' => $unit->product_unit_id, 'quantity' => 1]], 'payment' => 100,
        'delivery_required' => 1, 'customer_name' => str_repeat('a', 30), 'customer_contact_number' => '09123456789',
        'delivery_address' => str_repeat('a', 60), 'delivery_fee' => 0,
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('sales', ['customer_contact_number' => '09123456789', 'customer_name' => str_repeat('a', 30), 'delivery_address' => str_repeat('a', 60)]);
    $this->assertDatabaseCount('sale_items', 1);
    expect(SaleItem::sole()->baseStockQuantity())->toBe(1.0);
});

test('supplier add and edit forms render matching phone and text length restrictions', function () {
    $fixtures = contactValidationFixtures();

    $response = $this->actingAs($fixtures['owner'])->get(route('suppliers.index'));

    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    $phones = $xpath->query('//input[@name="contact_number"]');
    expect($phones->length)->toBe(2);
    foreach ($phones as $phone) {
        expect($phone->getAttribute('pattern'))->toBe('09[0-9]{9}');
        expect($phone->getAttribute('maxlength'))->toBe('11');
        expect($phone->getAttribute('minlength'))->toBe('11');
        expect($phone->getAttribute('type'))->toBe('tel');
        expect($phone->hasAttribute('required'))->toBeTrue();
    }
    foreach (['supplier_name' => '60', 'contact_person' => '60', 'email' => '60', 'address' => '200'] as $name => $maximum) {
        $fields = $xpath->query('//*[@name="'.$name.'"]');
        expect($fields->length)->toBe(2);
        foreach ($fields as $field) {
            expect($field->getAttribute('maxlength'))->toBe($maximum);
            expect($field->hasAttribute('required'))->toBeTrue();
        }
    }
    foreach ($xpath->query('//input[@name="product_ids[]"]') as $product) {
        expect($product->hasAttribute('required'))->toBeFalse();
    }
    foreach ($xpath->query('//input[@name="email"]') as $email) {
        expect($email->getAttribute('pattern'))->toBe('[^\s@]+@([gG][mM][aA][iI][lL]|[yY][aA][hH][oO][oO]|[oO][uU][tT][lL][oO][oO][kK])\.[cC][oO][mM]');
        expect($email->getAttribute('title'))->toBe('Use an email ending with @gmail.com, @yahoo.com, or @outlook.com.');
    }
});

test('cashiering renders strict phone validation and bounded delivery fields', function () {
    $fixtures = contactValidationFixtures();

    $response = $this->actingAs($fixtures['owner'])->get(route('sales.create'));

    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    $phone = $xpath->query('//input[@name="customer_contact_number"]')->item(0);
    expect($phone->getAttribute('pattern'))->toBe('09[0-9]{9}');
    expect($phone->getAttribute('maxlength'))->toBe('11');
    expect($phone->getAttribute('minlength'))->toBe('11');
    expect($phone->getAttribute('type'))->toBe('tel');
    expect($xpath->query('//input[@name="customer_name"]')->item(0)->getAttribute('maxlength'))->toBe('30');
    expect($xpath->query('//textarea[@name="delivery_address"]')->item(0)->getAttribute('maxlength'))->toBe('60');
});

test('delivery rejects customer details beyond the existing character limits', function (string $field, string $value, string $message) {
    $fixtures = contactValidationFixtures();
    $this->seed(SampleBulkMaterialsSeeder::class);
    $cement = Product::where('product_name', 'Portland Cement (Sample)')->firstOrFail();
    $unit = $cement->productUnits()->where('is_base_unit', true)->firstOrFail();

    $this->actingAs($fixtures['owner'])->post(route('sales.store'), [
        'items' => [['product_unit_id' => $unit->product_unit_id, 'quantity' => 1]], 'payment' => 100,
        'delivery_required' => 1, 'customer_name' => 'Customer', 'customer_contact_number' => '09123456789',
        'delivery_address' => 'Davao City', 'delivery_fee' => 0, $field => $value,
    ])->assertSessionHasErrors([$field => $message]);

    $this->assertDatabaseCount('sales', 0);
    $this->assertDatabaseCount('activity_logs', 0);
    $this->assertDatabaseHas('inventories', ['product_id' => $cement->product_id, 'quantity_on_hand' => 2000]);
})->with([
    'long customer name' => ['customer_name', str_repeat('a', 31), 'Customer name must not be longer than 30 characters.'],
    'long delivery address' => ['delivery_address', str_repeat('a', 61), 'Address must not be longer than 60 characters.'],
]);
