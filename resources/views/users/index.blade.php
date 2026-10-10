@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="toolbar"><a class="btn primary" href="{{ route('users.create') }}">+ Add User</a></div>
<table class="table">
    <thead><tr><th>Username</th><th>First Name</th><th>Last Name</th><th>Role</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>
        @foreach($users as $user)
            <tr>
                <td>{{ $user->username }}</td><td>{{ $user->first_name }}</td><td>{{ $user->last_name }}</td>
                <td>{{ $user->role === 'OWNER' ? 'Owner' : 'Sales Clerk' }}</td><td>{{ $user->is_active ? 'Active' : 'Inactive' }}</td>
                <td><div class="actions">
                    <button type="button" class="btn light small" data-edit-user="editUserModal{{ $user->user_id }}" aria-haspopup="dialog" aria-controls="editUserModal{{ $user->user_id }}">Edit</button>
                    @if($user->user_id !== auth()->id())
                        <form method="POST" action="{{ route('users.toggle', $user) }}">@csrf @method('PATCH')<button class="btn secondary small">{{ $user->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                    @endif
                </div></td>
            </tr>
        @endforeach
    </tbody>
</table>

@foreach($users as $user)
    @php($isEditing = $errors->any() && (string) old('_edit_user_id') === (string) $user->user_id)
    <div class="user-edit-overlay {{ $isEditing ? 'show' : '' }}" id="editUserModal{{ $user->user_id }}" data-user-edit-modal role="dialog" aria-modal="true" aria-hidden="{{ $isEditing ? 'false' : 'true' }}" aria-labelledby="editUserTitle{{ $user->user_id }}">
        <div class="user-edit-modal">
            <div class="user-edit-header">
                <div><h2 id="editUserTitle{{ $user->user_id }}">Edit User</h2><p>Update {{ $user->username }}’s account information.</p></div>
                <button type="button" class="btn light" data-close-user-edit aria-label="Close Edit User">&times;</button>
            </div>
            <form method="POST" action="{{ route('users.update', $user) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="_edit_user_id" value="{{ $user->user_id }}">
                <div class="user-edit-body">
                    @include('users.fields', ['user' => $user, 'useOldInput' => $isEditing, 'fieldPrefix' => 'edit-user-'.$user->user_id])
                </div>
                <div class="user-edit-footer"><button type="button" class="btn light" data-close-user-edit>Cancel</button><button type="submit" class="btn primary">Save Changes</button></div>
            </form>
        </div>
    </div>
@endforeach

<style>
.user-edit-overlay { display: none; position: fixed; inset: 0; z-index: 10000; background: rgba(15, 23, 42, .5); align-items: center; justify-content: center; padding: 24px; }
.user-edit-overlay.show { display: flex; }
.user-edit-modal { width: min(760px, 100%); max-height: 90vh; background: #fff; border-radius: 16px; display: flex; flex-direction: column; box-shadow: 0 20px 60px rgba(15, 23, 42, .2); }
.user-edit-header { display: flex; justify-content: space-between; align-items: center; padding: 22px 24px; border-bottom: 1px solid #edf1f6; }
.user-edit-header h2 { margin: 0 0 5px; font-size: 23px; }
.user-edit-header p { margin: 0; font-size: 13px; color: #64748b; }
.user-edit-modal form { min-height: 0; display: flex; flex-direction: column; }
.user-edit-body { padding: 24px; overflow-y: auto; }
.user-edit-footer { display: flex; justify-content: flex-end; gap: 8px; padding: 16px 24px; border-top: 1px solid #edf1f6; }
@media (max-width: 600px) {
    .user-edit-overlay { padding: 12px; }
    .user-edit-body .form-grid { grid-template-columns: 1fr; }
    .user-edit-header, .user-edit-body, .user-edit-footer { padding: 16px; }
}
</style>
@endsection

@push('scripts')
<script data-user-edit-script>
(() => {
    let activeModal = null;
    let activeTrigger = null;
    let previousOverflow = '';
    const focusableSelector = 'button:not([disabled]), input:not([type="hidden"]):not([disabled]), select:not([disabled])';

    function closeUserEdit() {
        if (!activeModal) {
            return;
        }
        activeModal.classList.remove('show');
        activeModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = previousOverflow;
        activeModal = null;
        activeTrigger?.focus();
        activeTrigger = null;
    }

    function openUserEdit(modal, trigger) {
        closeUserEdit();
        activeModal = modal;
        activeTrigger = trigger;
        previousOverflow = document.body.style.overflow;
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        modal.querySelector('input[name="username"]').focus();
    }

    const triggers = document.querySelectorAll('[data-edit-user]');
    triggers.forEach(button => {
        button.addEventListener('click', () => {
            const modal = document.getElementById(button.dataset.editUser);
            if (modal) {
                openUserEdit(modal, button);
            }
        });
    });

    document.querySelectorAll('[data-user-edit-modal]').forEach(modal => {
        modal.querySelectorAll('[data-close-user-edit]').forEach(button => button.addEventListener('click', closeUserEdit));
        modal.addEventListener('click', event => {
            if (event.target === modal) {
                closeUserEdit();
            }
        });
        if (modal.classList.contains('show')) {
            const trigger = Array.from(triggers).find(button => button.dataset.editUser === modal.id);
            openUserEdit(modal, trigger);
        }
    });

    document.addEventListener('keydown', event => {
        if (!activeModal) {
            return;
        }
        if (event.key === 'Escape') {
            closeUserEdit();
        } else if (event.key === 'Tab') {
            const fields = activeModal.querySelectorAll(focusableSelector);
            const first = fields[0];
            const last = fields[fields.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });
})();
</script>
@endpush
