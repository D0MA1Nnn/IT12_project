@extends('layouts.app')
@section('content')
<div class="top"><div><h1>{{ $user->exists ? 'Edit User' : 'Add User' }}</h1></div></div>
<div class="card">
    <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}">
        @csrf
        @if($user->exists) @method('PUT') @endif
        @include('users.fields', ['user' => $user, 'useOldInput' => true, 'fieldPrefix' => 'user-form'])
        <button class="btn primary">Save User</button>
    </form>
</div>
@endsection
