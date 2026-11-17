@extends('auth._shell')
@section('heading', 'Create your account')
@section('form')
<form method="post" action="{{ route('register') }}" class="mt-5 space-y-4">@csrf
    <div><label class="label" for="name">Full name</label><input id="name" name="name" class="field" value="{{ old('name') }}" required autocomplete="name"></div>
    <div><label class="label" for="email">Email</label><input id="email" name="email" type="email" class="field" value="{{ old('email') }}" required autocomplete="email"></div>
    <div><label class="label" for="phone">Phone (optional)</label><input id="phone" name="phone" class="field" value="{{ old('phone') }}" autocomplete="tel"></div>
    <div><label class="label" for="password">Password (min 8 characters)</label><input id="password" name="password" type="password" class="field" required minlength="8" autocomplete="new-password"></div>
    <div><label class="label" for="pc">Confirm password</label><input id="pc" name="password_confirmation" type="password" class="field" required autocomplete="new-password"></div>
    <button class="btn btn-primary w-full">Create account</button>
</form>
<p class="mt-5 text-center text-sm text-muted">Already registered? <a href="{{ route('login') }}" class="font-semibold text-brand">Log in</a></p>
@endsection
