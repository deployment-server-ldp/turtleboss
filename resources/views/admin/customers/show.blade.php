@extends('admin.layouts.admin')
@section('title', $customer->name)
@section('content')
<div class="grid gap-6 lg:grid-cols-[340px_1fr]">
    <div class="space-y-6">
        <form method="post" action="{{ route('admin.customers.update', $customer) }}" class="card space-y-3 p-5">@csrf @method('PUT')<h2 class="text-base">Customer details</h2>
            <input name="name" value="{{ old('name', $customer->name) }}" class="field" required><input name="email" type="email" value="{{ old('email', $customer->email) }}" class="field" required><input name="phone" value="{{ old('phone', $customer->phone) }}" class="field" placeholder="Phone">
            <textarea name="notes" rows="3" class="field" placeholder="Internal notes">{{ old('notes', $customer->notes) }}</textarea>
            @if($customer->user)<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($customer->user->is_active) class="rounded border-line"> Account active (can log in)</label>@else<p class="text-xs text-muted">Guest customer — no login account.</p>@endif
            <button class="btn btn-primary w-full">Save</button><p class="text-xs text-muted">Registered {{ $customer->created_at->format('d M Y') }}</p></form>
        <div class="card p-5 text-sm"><p class="text-xs text-muted">Total spending</p><p class="text-2xl font-bold">{{ money($customer->total_spent) }}</p><p class="text-xs text-muted">{{ $orders->count() }} order(s)</p></div>
        @if($customer->addresses->isNotEmpty())<div class="card p-5 text-sm"><h2 class="mb-2 text-base">Saved addresses</h2>@foreach($customer->addresses as $a)<p class="mb-2 text-muted">{{ $a->address }}, {{ $a->city }}</p>@endforeach</div>@endif
    </div>
    <div class="card self-start overflow-x-auto"><h2 class="p-4 text-base">Order history</h2><table class="w-full min-w-[480px] text-left"><tbody class="divide-y divide-line border-t border-line">@forelse($orders as $o)<tr><td class="px-4 py-3"><a href="{{ route('admin.orders.show', $o) }}" class="font-medium text-brand">{{ $o->number }}</a><p class="text-xs text-muted">{{ $o->created_at->format('d M Y') }}</p></td><td class="px-4 py-3"><span class="badge bg-navy-50 text-navy">{{ $o->status_label }}</span></td><td class="px-4 py-3 text-right font-semibold">{{ money($o->total) }}</td></tr>@empty<tr><td class="p-8 text-center text-muted">No orders.</td></tr>@endforelse</tbody></table></div>
</div>
@endsection
