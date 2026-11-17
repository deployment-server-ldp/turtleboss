<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><meta name="csrf-token" content="{{ csrf_token() }}"><title>Admin Login · Turtle Boss</title>
<link rel="stylesheet" href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap">@vite(['resources/css/app.css'])</head>
<body class="grid min-h-screen place-items-center bg-soft p-4">
<form method="post" action="{{ route('admin.login.post') }}" class="card w-full max-w-sm space-y-4 p-7">@csrf<input type="hidden" name="to" value="admin">
    <div class="text-center"><x-logo class="mx-auto h-10 w-auto"/><p class="mt-2 text-sm text-muted">Admin dashboard</p></div>
    @if($errors->any())<p class="rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</p>@endif
    <div><label class="label">Email</label><input name="email" type="email" class="field" value="{{ old('email') }}" required autofocus></div>
    <div><label class="label">Password</label><input name="password" type="password" class="field" required></div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" class="rounded border-line"> Keep me signed in</label>
    <button class="btn btn-primary w-full">Sign in</button>
</form></body></html>
