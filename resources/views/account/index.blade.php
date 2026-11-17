@extends('account._shell')
@section('body')
<div class="card p-6"><p class="text-lg font-semibold">Hello, {{ auth()->user()->name }}</p><p class="text-sm text-muted">{{ auth()->user()->email }} · {{ $orderCount }} {{ \Illuminate\Support\Str::plural('order', $orderCount) }}</p></div>
<h2 class="mb-3 mt-8 text-lg">Recent orders</h2>
@include('account._orders', ['orders' => $orders])
@endsection
