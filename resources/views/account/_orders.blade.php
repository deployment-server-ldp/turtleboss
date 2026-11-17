<div class="card divide-y divide-line">
    @forelse($orders as $o)
        <a href="{{ route('account.order', $o->number) }}" class="flex items-center justify-between gap-3 p-4 hover:bg-soft"><div><p class="font-semibold">{{ $o->number }}</p><p class="text-xs text-muted">{{ $o->created_at->format('d M Y') }}</p></div><span class="badge bg-navy-50 text-navy">{{ $o->status_label }}</span><b class="text-sm">{{ money($o->total) }}</b></a>
    @empty<p class="p-8 text-center text-muted">No orders yet. <a href="{{ route('shop') }}" class="text-brand underline">Start shopping</a></p>@endforelse
</div>
