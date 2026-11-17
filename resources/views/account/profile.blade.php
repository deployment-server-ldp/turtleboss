@extends('account._shell')
@section('body')
@if($errors->any())<div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
<form method="post" action="{{ route('account.profile.update') }}" class="card space-y-4 p-6">@csrf @method('PUT')<h2 class="text-lg">Profile</h2>
    <div class="grid gap-4 sm:grid-cols-2"><div><label class="label">Name</label><input name="name" class="field" value="{{ old('name', $user->name) }}" required></div><div><label class="label">Phone</label><input name="phone" class="field" value="{{ old('phone', $user->phone) }}"></div></div>
    <div><label class="label">Email</label><input name="email" type="email" class="field" value="{{ old('email', $user->email) }}" required></div><button class="btn btn-primary">Save profile</button></form>
<form method="post" action="{{ route('account.password') }}" class="card mt-6 space-y-4 p-6">@csrf @method('PUT')<h2 class="text-lg">Change password</h2>
    <div><label class="label">Current password</label><input name="current_password" type="password" class="field" required autocomplete="current-password"></div>
    <div class="grid gap-4 sm:grid-cols-2"><div><label class="label">New password</label><input name="password" type="password" class="field" required minlength="8" autocomplete="new-password"></div><div><label class="label">Confirm</label><input name="password_confirmation" type="password" class="field" required></div></div><button class="btn btn-primary">Update password</button></form>
@endsection
