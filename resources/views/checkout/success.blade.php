@extends('layouts.app')
@section('content')
<div class="container-x max-w-3xl py-12">
    <div class="text-center"><div class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-green-100 text-3xl text-green-700">✓</div>
        <h1 class="mt-4 text-3xl">Thank you, {{ $order->customer_name }}!</h1>
        <p class="mt-2 text-muted">Your order <b class="text-navy">{{ $order->number }}</b> has been placed. A confirmation was sent to {{ $order->customer_email }}.</p></div>
    <div class="card mt-8 divide-y divide-line">
        @foreach($order->items as $i)<div class="flex items-center gap-4 p-4"><img src="{{ img($i->image) }}" class="h-16 w-16 rounded-xl bg-soft object-cover" alt=""><div class="flex-1 text-sm"><p class="font-medium">{{ $i->name }}</p><p class="text-xs text-muted">{{ $i->variant_label }} × {{ $i->quantity }}</p></div><b class="text-sm">{{ money($i->line_total) }}</b></div>@endforeach
        <dl class="space-y-1.5 p-4 text-sm"><div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd>{{ money($order->subtotal) }}</dd></div>@if($order->discount_total > 0)<div class="flex justify-between text-green-700"><dt>Discount</dt><dd>− {{ money($order->discount_total) }}</dd></div>@endif<div class="flex justify-between"><dt class="text-muted">Shipping</dt><dd>{{ $order->shipping_total > 0 ? money($order->shipping_total) : 'Free' }}</dd></div><div class="flex justify-between border-t border-line pt-2 text-base font-bold"><dt>Total</dt><dd>{{ money($order->total) }}</dd></div></dl>
    </div>
    <div class="mt-6 grid gap-4 sm:grid-cols-2 text-sm">
        <div class="card p-4"><p class="font-semibold">Delivery to</p><p class="mt-1 text-muted">{{ $order->customer_name }}<br>{{ $order->shipping_address }}, {{ $order->shipping_city }}<br>{{ $order->customer_phone }}</p></div>
        <div class="card p-4"><p class="font-semibold">Payment: {{ $order->payment_label }}</p>@if($order->payment_method === 'bank_transfer')<p class="mt-1 whitespace-pre-line text-muted">{{ setting('bank_details') }}<br>Please use <b>{{ $order->number }}</b> as the reference and share the receipt via WhatsApp or reply to our email.</p>@else<p class="mt-1 text-muted">Pay in cash when your order arrives.</p>@endif</div>
    </div>
    <div class="mt-8 flex justify-center gap-3"><a href="{{ route('shop') }}" class="btn btn-primary">Continue shopping</a>@auth<a href="{{ route('account.orders') }}" class="btn btn-outline">My orders</a>@endauth</div>
</div>
@endsection
