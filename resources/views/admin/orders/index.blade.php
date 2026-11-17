@extends('admin.layouts.admin')
@section('title', 'Orders')
@section('content')
<div class="no-scrollbar mb-4 flex gap-2 overflow-x-auto">
    <a href="{{ route('admin.orders.index') }}" class="shrink-0 rounded-full border px-4 py-1.5 text-sm {{ !request('status') ? 'border-navy bg-navy text-white' : 'border-line bg-white' }}">All ({{ $counts->sum() }})</a>
    @foreach(\App\Models\Order::STATUSES as $k => $l)<a href="{{ route('admin.orders.index', ['status' => $k]) }}" class="shrink-0 rounded-full border px-4 py-1.5 text-sm {{ request('status') === $k ? 'border-navy bg-navy text-white' : 'border-line bg-white' }}">{{ $l }} ({{ $counts[$k] ?? 0 }})</a>@endforeach
</div>
<form method="get" class="mb-4 flex flex-wrap gap-2"><input type="hidden" name="status" value="{{ request('status') }}">
    <input name="q" value="{{ request('q') }}" placeholder="Order #, name, email, phone" class="field !w-64 !py-2">
    <select name="payment" class="field !w-44 !py-2"><option value="">Any payment</option>@foreach(\App\Models\Order::PAYMENT_METHODS as $k => $l)<option value="{{ $k }}" @selected(request('payment') === $k)>{{ $l }}</option>@endforeach</select>
    <input type="date" name="from" value="{{ request('from') }}" class="field !w-40 !py-2"><input type="date" name="to" value="{{ request('to') }}" class="field !w-40 !py-2"><button class="btn btn-outline btn-sm">Filter</button></form>
<div class="card overflow-x-auto"><table class="w-full min-w-[760px] text-left"><thead class="border-b border-line bg-soft text-xs uppercase text-muted"><tr><th class="px-4 py-3">Order</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Customer</th><th class="px-4 py-3">Payment</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Total</th></tr></thead><tbody class="divide-y divide-line">
@forelse($orders as $o)<tr class="hover:bg-soft/60"><td class="px-4 py-3"><a href="{{ route('admin.orders.show', $o) }}" class="font-semibold text-brand">{{ $o->number }}</a><p class="text-xs text-muted">{{ $o->items_count }} item(s)</p></td><td class="px-4 py-3">{{ $o->created_at->format('d M Y H:i') }}</td><td class="px-4 py-3">{{ $o->customer_name }}<p class="text-xs text-muted">{{ $o->shipping_city }}</p></td><td class="px-4 py-3">{{ $o->payment_label }}<p class="text-xs {{ $o->payment_status === 'paid' ? 'text-green-700' : 'text-muted' }}">{{ ucfirst(str_replace('_', ' ', $o->payment_status)) }}</p></td><td class="px-4 py-3"><span class="badge bg-navy-50 text-navy">{{ $o->status_label }}</span></td><td class="px-4 py-3 text-right font-semibold">{{ money($o->total) }}</td></tr>
@empty<tr><td colspan="6" class="px-4 py-14 text-center text-muted">No orders found.</td></tr>@endforelse</tbody></table></div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
