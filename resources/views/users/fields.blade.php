@php
    $username = $useOldInput ? old('username', $user->username) : $user->username;
    $role = $useOldInput ? old('role', $user->role) : $user->role;
    $firstName = $useOldInput ? old('first_name', $user->first_name) : $user->first_name;
    $lastName = $useOldInput ? old('last_name', $user->last_name) : $user->last_name;
@endphp
<div class="form-grid">
    <div class="field"><label for="{{ $fieldPrefix }}-username">Username</label><input class="input" id="{{ $fieldPrefix }}-username" name="username" value="{{ $username }}" maxlength="100" required autocomplete="username"></div>
    <div class="field"><label for="{{ $fieldPrefix }}-role">Role</label><select id="{{ $fieldPrefix }}-role" name="role" required><option value="OWNER" @selected($role === 'OWNER')>Owner</option><option value="SALES_CLERK" @selected($role === 'SALES_CLERK')>Sales Clerk</option></select></div>
    <div class="field"><label for="{{ $fieldPrefix }}-first-name">First Name</label><input class="input" id="{{ $fieldPrefix }}-first-name" name="first_name" value="{{ $firstName }}" maxlength="100" autocomplete="given-name"></div>
    <div class="field"><label for="{{ $fieldPrefix }}-last-name">Last Name</label><input class="input" id="{{ $fieldPrefix }}-last-name" name="last_name" value="{{ $lastName }}" maxlength="100" autocomplete="family-name"></div>
    <div class="field"><label for="{{ $fieldPrefix }}-password">Password {{ $user->exists ? '(leave blank to keep current)' : '' }}</label><input class="input" id="{{ $fieldPrefix }}-password" type="password" name="password" minlength="6" autocomplete="new-password" @required(!$user->exists)></div>
    <div class="field"><label for="{{ $fieldPrefix }}-confirmation">Confirm Password</label><input class="input" id="{{ $fieldPrefix }}-confirmation" type="password" name="password_confirmation" autocomplete="new-password" @required(!$user->exists)></div>
</div>
