@extends('account._shell')
@section('body')
<div class="flex items-center justify-between"><h2 class="text-lg">Order {{ $order->number }}</h2><span class="badge bg-navy-50 text-navy">{{ $order->status_label }}</span></div>
<p class="text-sm text-muted">Placed {{ $order->created_at->format('d M Y, h:i A') }} · {{ $order->payment_label }} · Payment: {{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}</p>
@if($order->tracking_number)<p class="mt-2 rounded-xl bg-green-50 p-3 text-sm text-green-800">Tracking: <b>{{ $order->tracking_number }}</b> {{ $order->courier ? '(' . $order->courier . ')' : '' }}</p>@endif
<div class="card mt-5 divide-y divide-line">
    @foreach($order->items as $i)<div class="flex items-center gap-4 p-4"><img src="{{ img($i->image) }}" class="h-16 w-16 rounded-xl bg-soft object-cover" alt="">
        <div class="flex-1 text-sm">@if($i->product)<a href="{{ $i->product->url }}" class="font-medium hover:underline">{{ $i->name }}</a>@else<p class="font-medium">{{ $i->name }}</p>@endif<p class="text-xs text-muted">{{ $i->variant_label }} × {{ $i->quantity }}</p></div><b class="text-sm">{{ money($i->line_total) }}</b></div>@endforeach
    <dl class="space-y-1.5 p-4 text-sm"><div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd>{{ money($order->subtotal) }}</dd></div>@if($order->discount_total > 0)<div class="flex justify-between text-green-700"><dt>Discount</dt><dd>− {{ money($order->discount_total) }}</dd></div>@endif<div class="flex justify-between"><dt class="text-muted">Shipping</dt><dd>{{ money($order->shipping_total) }}</dd></div><div class="flex justify-between border-t border-line pt-2 font-bold"><dt>Total</dt><dd>{{ money($order->total) }}</dd></div></dl>
</div>
<div class="card mt-5 p-4 text-sm"><p class="font-semibold">Shipping address</p><p class="text-muted">{{ $order->customer_name }}, {{ $order->shipping_address }}, {{ $order->shipping_city }} {{ $order->shipping_postal_code }}<br>{{ $order->customer_phone }}</p></div>
@endsection
