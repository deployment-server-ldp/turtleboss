@extends('auth._shell')
@section('heading', 'Forgot password')
@section('form')
@if(session('status'))<div class="mt-4 rounded-xl bg-green-50 p-3 text-sm text-green-800">{{ session('status') }}</div>@endif
<form method="post" action="{{ route('password.email') }}" class="mt-5 space-y-4">@csrf
    <p class="text-sm text-muted">Enter your email and we'll send you a reset link.</p>
    <div><label class="label" for="email">Email</label><input id="email" name="email" type="email" class="field" required autofocus></div>
    <button class="btn btn-primary w-full">Send reset link</button>
</form>
@endsection
