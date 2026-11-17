@extends('admin.layouts.admin')
@section('title', 'Dashboard')
@section('content')
<div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-6">
    @foreach($cards as [$l, $v, $sub])
        <div class="card p-4"><p class="text-xs text-muted">{{ $l }}</p><p class="mt-1 text-xl font-bold sm:text-2xl">{{ $v }}</p><p class="mt-0.5 text-[11px] text-muted">{{ $sub }}</p></div>
    @endforeach
</div>
<div class="mt-6 grid gap-6 lg:grid-cols-2">
    @foreach([['Revenue – last 30 days', 'revenue', money($revenue30)], ['Orders – last 30 days', 'orders', $orders30 . ' orders']] as [$t, $k, $sum])
        <div class="card p-5" x-data="chart(@js($series), '{{ $k }}')">
            <div class="flex items-baseline justify-between"><h2 class="text-base">{{ $t }}</h2><span class="text-sm font-semibold">{{ $sum }}</span></div>
            <div class="relative mt-4 flex h-44 items-end gap-[3px]" @mouseleave="hover = null">
                <template x-for="(s, i) in series" :key="i"><div class="group relative flex-1" style="height:100%" @mouseenter="hover = i"><div class="absolute bottom-0 w-full rounded-t bg-navy/80 transition hover:bg-navy" :style="'height:' + (s[key] > 0 ? h(s) : 1) + '%; opacity:' + (s[key] > 0 ? 1 : .15)"></div></div></template>
                <div x-show="hover !== null" x-cloak class="pointer-events-none absolute -top-2 left-1/2 -translate-x-1/2 rounded-lg bg-navy px-3 py-1.5 text-xs text-white shadow" x-text="hover !== null ? series[hover].label + ': ' + (key === 'revenue' ? 'Rs. ' + Math.round(series[hover].revenue).toLocaleString() : series[hover].orders) : ''"></div>
            </div>
            <div class="mt-2 flex justify-between text-[10px] text-muted"><span>{{ $series->first()['label'] }}</span><span>{{ $series->last()['label'] }}</span></div>
            @if($sum === money(0) || $sum === '0 orders')<p class="mt-2 text-xs text-muted">No sales in this period yet.</p>@endif
        </div>
    @endforeach
</div>
<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <div class="card overflow-hidden lg:col-span-2"><div class="flex items-center justify-between p-4"><h2 class="text-base">Recent Orders</h2><a href="{{ route('admin.orders.index') }}" class="text-sm text-brand">View all</a></div>
        <div class="overflow-x-auto"><table class="w-full min-w-[520px] text-left"><thead class="bg-soft text-xs uppercase text-muted"><tr><th class="px-4 py-2">Order</th><th class="px-4 py-2">Customer</th><th class="px-4 py-2">Status</th><th class="px-4 py-2 text-right">Total</th></tr></thead><tbody class="divide-y divide-line">
            @forelse($recentOrders as $o)<tr><td class="px-4 py-2.5"><a href="{{ route('admin.orders.show', $o) }}" class="font-medium text-brand">{{ $o->number }}</a><p class="text-xs text-muted">{{ $o->created_at->diffForHumans() }}</p></td><td class="px-4 py-2.5">{{ $o->customer_name }}</td><td class="px-4 py-2.5"><span class="badge bg-navy-50 text-navy">{{ $o->status_label }}</span></td><td class="px-4 py-2.5 text-right font-semibold">{{ money($o->total) }}</td></tr>
            @empty<tr><td colspan="4" class="px-4 py-8 text-center text-muted">No orders yet.</td></tr>@endforelse</tbody></table></div></div>
    <div class="card p-4"><h2 class="mb-3 text-base">Recent Customers</h2><ul class="space-y-3">@forelse($recentCustomers as $c)<li class="flex items-center gap-3"><span class="grid h-9 w-9 place-items-center rounded-full bg-navy-50 font-semibold text-navy">{{ strtoupper(substr($c->name, 0, 1)) }}</span><div class="min-w-0"><a href="{{ route('admin.customers.show', $c) }}" class="block truncate font-medium">{{ $c->name }}</a><p class="truncate text-xs text-muted">{{ $c->email }}</p></div></li>@empty<li class="text-muted">No customers yet.</li>@endforelse</ul></div>
    <div class="card p-4"><h2 class="mb-3 text-base">Best Selling Products</h2><ol class="space-y-2.5">@forelse($top as $t)<li class="flex justify-between gap-3"><span class="truncate">{{ $t->name }}</span><span class="shrink-0 text-muted">{{ $t->qty }} sold</span></li>@empty<li class="text-muted">No sales data yet.</li>@endforelse</ol></div>
    <div class="card p-4"><h2 class="mb-3 text-base">Top Performing Categories</h2><ol class="space-y-2.5">@forelse($topCats as $c)<li class="flex justify-between gap-3"><span class="truncate">{{ $c->name }}</span><span class="shrink-0 font-medium">{{ money($c->revenue) }}</span></li>@empty<li class="text-muted">No sales data yet.</li>@endforelse</ol></div>
</div>
@endsection
