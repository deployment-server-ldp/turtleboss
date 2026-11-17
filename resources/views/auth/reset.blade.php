@extends('auth._shell')
@section('heading', 'Choose a new password')
@section('form')
<form method="post" action="{{ route('password.update') }}" class="mt-5 space-y-4">@csrf
    <input type="hidden" name="token" value="{{ $token }}"><input type="hidden" name="email" value="{{ $email }}">
    <div><label class="label" for="password">New password</label><input id="password" name="password" type="password" class="field" required minlength="8" autocomplete="new-password"></div>
    <div><label class="label" for="pc">Confirm password</label><input id="pc" name="password_confirmation" type="password" class="field" required></div>
    <button class="btn btn-primary w-full">Reset password</button>
</form>
@endsection
