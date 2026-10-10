<?php

use App\Models\UnitOfMeasure;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['services.online_backup.path' => null]);
});

function createUnitModalOwner(): User
{
    return User::forceCreate([
        'username' => 'unit-modal-owner', 'password_hash' => Hash::make('secret'),
        'role' => 'OWNER', 'is_active' => true,
    ]);
}

function unitModalDocument(string $html): DOMXPath
{
    $document = new DOMDocument;
    @$document->loadHTML($html);

    return new DOMXPath($document);
}

test('units page places the add form in a hidden dialog opened from the list header', function () {
    $owner = createUnitModalOwner();
    $unit = UnitOfMeasure::create([
        'unit_name' => 'Piece', 'unit_symbol' => 'pc', 'unit_type' => 'COUNT', 'is_active' => true,
    ]);

    $response = $this->actingAs($owner)->get(route('units.index'))->assertSee('Available Units')->assertSee('Piece');

    $xpath = unitModalDocument($response->getContent());
    $dialog = $xpath->query('//*[@id="addUnitFormModal"]')->item(0);
    expect(trim($dialog->getAttribute('class')))->toBe('units-modal-overlay');
    expect($dialog->getAttribute('role'))->toBe('dialog');
    expect($xpath->query('//*[@id="addUnitFormModal"]//form[@id="addUnitForm"]')->length)->toBe(1);
    expect($xpath->query('//*[@id="addUnitForm"]//input[@name="_token"]')->length)->toBe(1);
    expect($xpath->query('//*[@id="addUnitForm"]//*[@required]')->length)->toBe(3);
    expect($xpath->query('//*[@id="addUnitForm"]//select[@name="unit_type"]/option')->length)->toBe(5);
    expect($xpath->query('//*[contains(@class,"units-list-heading")]//button[@id="openAddUnitFormModal"]')->length)->toBe(1);
    expect($xpath->query('//*[contains(@class,"units-list-card")]//form[@id="addUnitForm"]')->length)->toBe(0);
    expect($xpath->query('//button[contains(@class,"unit-edit-button")]')->length)->toBe(1);
    expect($xpath->query('//form[contains(@class,"unit-toggle-form")]')->length)->toBe(1);
    $this->assertModelExists($unit);
});

test('confirmed unit additions save an active unit and show the existing success popup', function () {
    $owner = createUnitModalOwner();

    $this->actingAs($owner)->post(route('units.store'), [
        'unit_name' => 'Meter', 'unit_symbol' => 'm', 'unit_type' => 'LENGTH',
    ])->assertRedirect(route('units.index'))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('units_of_measure', [
        'unit_name' => 'Meter', 'unit_symbol' => 'm', 'unit_type' => 'LENGTH', 'is_active' => true,
    ]);
    $unit = UnitOfMeasure::where('unit_name', 'Meter')->firstOrFail();
    $this->assertDatabaseHas('activity_logs', [
        'user_id' => $owner->user_id, 'module' => 'UNIT', 'action' => 'CREATE',
        'reference_type' => 'UnitOfMeasure', 'reference_id' => $unit->unit_id,
    ]);
    $response = $this->get(route('units.index'))->assertSee('data-success-toast', false)
        ->assertSee('Unit of measure added successfully.');
    $dialog = unitModalDocument($response->getContent())->query('//*[@id="addUnitFormModal"]')->item(0);
    expect(trim($dialog->getAttribute('class')))->toBe('units-modal-overlay');
});

test('missing unit fields reopen the add dialog and do not save anything', function () {
    $owner = createUnitModalOwner();

    $this->actingAs($owner)->from(route('units.index'))->post(route('units.store'), [])
        ->assertRedirect(route('units.index'))->assertSessionHasErrors(['unit_name', 'unit_symbol', 'unit_type']);

    $response = $this->get(route('units.index'))->assertSee('The unit name field is required.')
        ->assertSee('The unit symbol field is required.')->assertSee('The unit type field is required.');
    $dialog = unitModalDocument($response->getContent())->query('//*[@id="addUnitFormModal"]')->item(0);
    expect(trim($dialog->getAttribute('class')))->toBe('units-modal-overlay show');
    $this->assertDatabaseCount('units_of_measure', 0);
    $this->assertDatabaseCount('activity_logs', 0);
});

test('duplicate units reopen the add dialog with the entered values preserved', function () {
    $owner = createUnitModalOwner();
    UnitOfMeasure::create(['unit_name' => 'Piece', 'unit_symbol' => 'pc', 'unit_type' => 'COUNT', 'is_active' => true]);

    $this->actingAs($owner)->from(route('units.index'))->post(route('units.store'), [
        'unit_name' => 'Piece', 'unit_symbol' => 'pcs', 'unit_type' => 'COUNT',
    ])->assertSessionHasErrors(['unit_name' => 'The unit name has already been taken.']);

    $response = $this->get(route('units.index'))->assertSee('The unit name has already been taken.');
    $xpath = unitModalDocument($response->getContent());
    expect(trim($xpath->query('//*[@id="addUnitFormModal"]')->item(0)->getAttribute('class')))->toBe('units-modal-overlay show');
    expect($xpath->query('//*[@id="unit_name"]')->item(0)->getAttribute('value'))->toBe('Piece');
    expect($xpath->query('//*[@id="unit_symbol"]')->item(0)->getAttribute('value'))->toBe('pcs');
    expect($xpath->query('//*[@id="unit_type"]/option[@selected]')->item(0)->getAttribute('value'))->toBe('COUNT');
    $this->assertDatabaseCount('units_of_measure', 1);
    $this->assertDatabaseCount('activity_logs', 0);
});

test('edit validation errors do not open the add unit dialog', function () {
    $owner = createUnitModalOwner();
    $unit = UnitOfMeasure::create(['unit_name' => 'Piece', 'unit_symbol' => 'pc', 'unit_type' => 'COUNT', 'is_active' => true]);

    $this->actingAs($owner)->from(route('units.index'))->post(route('units.update', $unit), [
        '_method' => 'PUT', 'unit_name' => '', 'unit_symbol' => 'pc', 'unit_type' => 'COUNT',
    ])->assertSessionHasErrors('unit_name');

    $response = $this->get(route('units.index'))->assertSee('The unit name field is required.');
    $dialog = unitModalDocument($response->getContent())->query('//*[@id="addUnitFormModal"]')->item(0);
    expect(trim($dialog->getAttribute('class')))->toBe('units-modal-overlay');
    $this->assertDatabaseHas('units_of_measure', ['unit_id' => $unit->unit_id, 'unit_name' => 'Piece']);
    $this->assertDatabaseCount('activity_logs', 0);
});

test('invalid additions cannot inject markup into the reopened unit dialog', function () {
    $owner = createUnitModalOwner();
    $name = '<script>alert("unit")</script>';
    $symbol = '" onfocus="alert(1)';

    $this->actingAs($owner)->from(route('units.index'))->post(route('units.store'), [
        'unit_name' => $name, 'unit_symbol' => $symbol, 'unit_type' => 'INVALID',
    ])->assertSessionHasErrors(['unit_type' => 'The selected unit type is invalid.']);

    $this->get(route('units.index'))->assertSee($name)->assertDontSee($name, false)
        ->assertDontSee('value="" onfocus="alert(1)', false);
    $this->assertDatabaseCount('units_of_measure', 0);
    $this->assertDatabaseCount('activity_logs', 0);
});
