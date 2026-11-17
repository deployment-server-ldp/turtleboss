@extends('admin.layouts.admin')
@section('title', 'Customers')
@section('content')
<div class="mb-4 flex flex-wrap gap-2"><form class="flex gap-2" method="get"><input name="q" value="{{ request('q') }}" placeholder="Search name, email, phone" class="field !w-64 !py-2"><button class="btn btn-outline btn-sm">Search</button></form><a href="{{ route('admin.customers.export') }}" class="btn btn-outline btn-sm ml-auto">⬇ Export CSV</a></div>
<div class="card overflow-x-auto"><table class="w-full min-w-[680px] text-left"><thead class="border-b border-line bg-soft text-xs uppercase text-muted"><tr><th class="px-4 py-3">Customer</th><th class="px-4 py-3">Phone</th><th class="px-4 py-3">Orders</th><th class="px-4 py-3"><a href="{{ request()->fullUrlWithQuery(['sort' => 'spent']) }}">Total spent ↓</a></th><th class="px-4 py-3">Registered</th></tr></thead><tbody class="divide-y divide-line">
@forelse($customers as $c)<tr class="hover:bg-soft/60"><td class="px-4 py-3"><a href="{{ route('admin.customers.show', $c) }}" class="font-medium text-brand">{{ $c->name }}</a><p class="text-xs text-muted">{{ $c->email }} {{ $c->user_id ? '' : '· guest' }}</p></td><td class="px-4 py-3">{{ $c->phone }}</td><td class="px-4 py-3">{{ $c->orders_count }}</td><td class="px-4 py-3 font-semibold">{{ money($c->spent ?? 0) }}</td><td class="px-4 py-3">{{ $c->created_at->format('d M Y') }}</td></tr>
@empty<tr><td colspan="5" class="px-4 py-14 text-center text-muted">No customers yet.</td></tr>@endforelse</tbody></table></div><div class="mt-4">{{ $customers->links() }}</div>
@endsection
