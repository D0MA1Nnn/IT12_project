<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['services.online_backup.path' => null]);
});

function userEditAccount(string $username, string $role = 'OWNER'): User
{
    return User::forceCreate([
        'username' => $username, 'first_name' => 'Existing', 'last_name' => 'Person',
        'password_hash' => Hash::make('original-secret'), 'role' => $role, 'is_active' => true,
    ]);
}

function userEditDocument(string $html): DOMXPath
{
    $document = new DOMDocument;
    @$document->loadHTML($html);

    return new DOMXPath($document);
}

test('User Management Edit buttons open hidden per-user forms with existing update routes', function () {
    $owner = userEditAccount('edit-owner');
    $clerk = userEditAccount('edit-clerk', 'SALES_CLERK');

    $response = $this->actingAs($owner)->get(route('users.index'));

    $response->assertSee('data-edit-user="editUserModal'.$clerk->user_id.'"', false)
        ->assertDontSee('href="'.route('users.edit', $clerk).'"', false)
        ->assertSee('+ Add User')->assertSee('Deactivate')->assertDontSee($owner->password_hash, false);
    $xpath = userEditDocument($response->getContent());
    $modal = $xpath->query('//*[@id="editUserModal'.$clerk->user_id.'"]')->item(0);
    expect(trim($modal->getAttribute('class')))->toBe('user-edit-overlay');
    expect($modal->getAttribute('aria-hidden'))->toBe('true');
    expect($xpath->query('//*[@id="editUserModal'.$clerk->user_id.'"]//form[@action="'.route('users.update', $clerk).'"]')->length)->toBe(1);
    expect($xpath->query('//*[@id="editUserModal'.$clerk->user_id.'"]//input[@name="_token"]')->length)->toBe(1);
    expect($xpath->query('//*[@id="editUserModal'.$clerk->user_id.'"]//input[@name="_method"]')->item(0)->getAttribute('value'))->toBe('PUT');
    expect($xpath->query('//*[@id="editUserModal'.$clerk->user_id.'"]//input[@name="username"]')->item(0)->getAttribute('value'))->toBe('edit-clerk');
    expect($xpath->query('//*[@id="editUserModal'.$clerk->user_id.'"]//input[@type="password" and @required]')->length)->toBe(0);
    $this->assertDatabaseCount('activity_logs', 0);
});

test('saving profile changes with blank password preserves the password and returns to the list', function () {
    $owner = userEditAccount('edit-owner');
    $clerk = userEditAccount('edit-clerk', 'SALES_CLERK');
    $originalHash = $clerk->password_hash;

    $response = $this->actingAs($owner)->from(route('users.index'))->put(route('users.update', $clerk), [
        '_edit_user_id' => $clerk->user_id, 'username' => 'updated-clerk', 'first_name' => 'Updated',
        'last_name' => 'Employee', 'role' => 'SALES_CLERK', 'password' => '', 'password_confirmation' => '',
    ]);

    $response->assertRedirect(route('users.index'))->assertSessionHasNoErrors()->assertSessionHas('success', 'User updated successfully.');
    $this->assertDatabaseHas('users', ['user_id' => $clerk->user_id, 'username' => 'updated-clerk', 'first_name' => 'Updated', 'last_name' => 'Employee', 'password_hash' => $originalHash]);
    $this->assertDatabaseHas('activity_logs', ['module' => 'USER', 'action' => 'UPDATE', 'user_id' => $owner->user_id, 'reference_id' => $clerk->user_id]);
    $this->get(route('users.index'))->assertSee('data-success-toast', false)->assertSee('updated-clerk');
});

test('saving a confirmed new password changes the stored hash and does not expose it', function () {
    $owner = userEditAccount('edit-owner');
    $clerk = userEditAccount('edit-clerk', 'SALES_CLERK');

    $response = $this->actingAs($owner)->put(route('users.update', $clerk), [
        '_edit_user_id' => $clerk->user_id, 'username' => $clerk->username, 'role' => 'SALES_CLERK',
        'password' => 'updated-secret', 'password_confirmation' => 'updated-secret',
    ]);

    $response->assertRedirect(route('users.index'))->assertSessionHasNoErrors();
    $clerk->refresh();
    expect(Hash::check('updated-secret', $clerk->password_hash))->toBeTrue();
    expect(Hash::check('original-secret', $clerk->password_hash))->toBeFalse();
    expect($clerk->toArray())->not->toHaveKey('password_hash');
    $this->get(route('users.index'))->assertDontSee('updated-secret')->assertDontSee($clerk->password_hash, false);
});

test('Add User still creates a usable password after sharing its fields with the edit popup', function () {
    $owner = userEditAccount('edit-owner');

    $response = $this->actingAs($owner)->post(route('users.store'), [
        'username' => 'created-clerk', 'first_name' => 'New', 'last_name' => 'Employee',
        'role' => 'SALES_CLERK', 'password' => 'created-secret', 'password_confirmation' => 'created-secret',
    ]);

    $response->assertRedirect(route('users.index'))->assertSessionHasNoErrors();
    $created = User::where('username', 'created-clerk')->firstOrFail();
    expect(Hash::check('created-secret', $created->password_hash))->toBeTrue();
    $this->assertDatabaseHas('activity_logs', ['module' => 'USER', 'action' => 'CREATE', 'reference_id' => $created->user_id]);
    $this->get(route('users.create'))->assertSee('Add User')->assertSee('name="password" minlength="6" autocomplete="new-password" required', false);
});

test('invalid updates reopen only the edited user popup without changing another user or saving a password', function (array $invalid, string $field, string $message) {
    $owner = userEditAccount('edit-owner');
    $clerk = userEditAccount('edit-clerk', 'SALES_CLERK');
    $originalHash = $clerk->password_hash;
    $input = ['_edit_user_id' => $clerk->user_id, 'username' => 'entered-name', 'first_name' => 'Entered', 'role' => 'SALES_CLERK', ...$invalid];

    $this->actingAs($owner)->from(route('users.index'))->put(route('users.update', $clerk), $input)
        ->assertRedirect(route('users.index'))->assertSessionHasErrors([$field => $message]);

    $response = $this->get(route('users.index'))->assertSee($message);
    $xpath = userEditDocument($response->getContent());
    expect($xpath->query('//*[@id="editUserModal'.$clerk->user_id.'"]')->item(0)->getAttribute('aria-hidden'))->toBe('false');
    expect($xpath->query('//*[@id="editUserModal'.$owner->user_id.'"]')->item(0)->getAttribute('aria-hidden'))->toBe('true');
    expect($xpath->query('//*[@id="editUserModal'.$clerk->user_id.'"]//input[@name="first_name"]')->item(0)->getAttribute('value'))->toBe('Entered');
    expect($xpath->query('//*[@id="editUserModal'.$owner->user_id.'"]//input[@name="first_name"]')->item(0)->getAttribute('value'))->toBe('Existing');
    $this->assertDatabaseHas('users', ['user_id' => $clerk->user_id, 'username' => 'edit-clerk', 'password_hash' => $originalHash]);
    $this->assertDatabaseCount('activity_logs', 0);
})->with([
    'required username' => [['username' => ''], 'username', 'The username field is required.'],
    'duplicate username' => [['username' => 'edit-owner'], 'username', 'The username has already been taken.'],
    'invalid role' => [['role' => 'ADMIN'], 'role', 'The selected role is invalid.'],
    'short password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password', 'The password field must be at least 6 characters.'],
    'unconfirmed password' => [['password' => 'updated-secret', 'password_confirmation' => 'different-secret'], 'password', 'The password field confirmation does not match.'],
]);

test('user edit popup escapes stored usernames and names', function () {
    $owner = userEditAccount('edit-owner');
    $unsafe = '<img src=x onerror=alert(1)>';
    $clerk = userEditAccount('edit-clerk', 'SALES_CLERK');
    $clerk->update(['username' => $unsafe, 'first_name' => $unsafe, 'last_name' => $unsafe]);

    $response = $this->actingAs($owner)->get(route('users.index'));

    $response->assertSee($unsafe)->assertDontSee($unsafe, false);
});

test('sales clerks and guests cannot load or submit user edits', function () {
    $owner = userEditAccount('edit-owner');
    $clerk = userEditAccount('edit-clerk', 'SALES_CLERK');

    $this->get(route('users.index'))->assertRedirect(route('login'));
    $this->put(route('users.update', $owner), [])->assertRedirect(route('login'));
    $this->actingAs($clerk)->get(route('users.index'))->assertForbidden();
    $this->put(route('users.update', $owner), ['username' => 'changed', 'role' => 'OWNER'])->assertForbidden();
    $this->assertDatabaseHas('users', ['user_id' => $owner->user_id, 'username' => 'edit-owner']);
    $this->assertDatabaseCount('activity_logs', 0);
});
