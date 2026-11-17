@extends('admin.layouts.admin')
@section('title', 'Stock adjustment history')
@section('content')
<div class="card overflow-x-auto"><table class="w-full min-w-[640px] text-left"><thead class="border-b border-line bg-soft text-xs uppercase text-muted"><tr><th class="px-4 py-3">When</th><th class="px-4 py-3">Item</th><th class="px-4 py-3">Reason</th><th class="px-4 py-3">Change</th><th class="px-4 py-3">By</th></tr></thead><tbody class="divide-y divide-line">
@forelse($logs as $l)<tr><td class="px-4 py-2.5 text-xs">{{ $l->created_at->format('d M Y H:i') }}</td><td class="px-4 py-2.5">{{ $l->product?->name }} <span class="text-xs text-muted">{{ $l->variant?->label }}</span></td><td class="px-4 py-2.5">{{ ucfirst($l->reason) }} <span class="text-xs text-muted">{{ $l->note }}</span></td><td class="px-4 py-2.5 font-semibold {{ $l->change < 0 ? 'text-red-600' : 'text-green-700' }}">{{ $l->change > 0 ? '+' : '' }}{{ $l->change }} → {{ $l->qty_after }}</td><td class="px-4 py-2.5">{{ $l->user?->name ?? 'System' }}</td></tr>
@empty<tr><td colspan="5" class="px-4 py-14 text-center text-muted">No stock changes recorded yet.</td></tr>@endforelse</tbody></table></div><div class="mt-4">{{ $logs->links() }}</div>
@endsection
