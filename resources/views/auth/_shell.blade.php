@extends('layouts.app')
@section('content')
<div class="container-x grid min-h-[60vh] place-items-center py-10"><div class="card w-full max-w-md p-6 sm:p-8">
    <h1 class="text-2xl">@yield('heading')</h1>
    @if($errors->any())<div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
    @yield('form')
</div></div>
@endsection
