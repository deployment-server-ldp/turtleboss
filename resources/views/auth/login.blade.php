@extends('auth._shell')
@section('heading', 'Welcome back')
@section('form')
<form method="post" action="{{ route('login') }}" class="mt-5 space-y-4">@csrf
    <div><label class="label" for="email">Email</label><input id="email" name="email" type="email" class="field" value="{{ old('email') }}" required autofocus autocomplete="email"></div>
    <div><label class="label" for="password">Password</label><input id="password" name="password" type="password" class="field" required autocomplete="current-password"></div>
    <div class="flex items-center justify-between text-sm"><label class="flex items-center gap-2"><input type="checkbox" name="remember" class="rounded border-line"> Remember me</label><a href="{{ route('password.request') }}" class="text-brand hover:underline">Forgot password?</a></div>
    <button class="btn btn-primary w-full">Log in</button>
</form>
<p class="mt-5 text-center text-sm text-muted">New here? <a href="{{ route('register') }}" class="font-semibold text-brand">Create an account</a></p>
@endsection
