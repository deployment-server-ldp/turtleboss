@extends('admin.layouts.admin')
@section('title', 'Inventory')
@section('content')
<div class="mb-4 flex flex-wrap items-center gap-2">
    @foreach([null => 'All', 'low' => 'Low stock', 'out' => 'Out of stock'] as $k => $l)<a href="{{ route('admin.inventory.index', array_filter(['filter' => $k, 'q' => request('q')])) }}" class="rounded-full border px-4 py-1.5 text-sm {{ request('filter') == $k ? 'border-navy bg-navy text-white' : 'border-line bg-white' }}">{{ $l }}</a>@endforeach
    <form class="ml-auto flex gap-2" method="get"><input type="hidden" name="filter" value="{{ request('filter') }}"><input name="q" value="{{ request('q') }}" placeholder="Search name or SKU" class="field !w-56 !py-2"><button class="btn btn-outline btn-sm">Search</button></form>
    <a href="{{ route('admin.inventory.history') }}" class="btn btn-outline btn-sm">Stock history</a>
</div>
<div class="card overflow-x-auto"><table class="w-full min-w-[720px] text-left"><thead class="border-b border-line bg-soft text-xs uppercase text-muted"><tr><th class="px-4 py-3">Item</th><th class="px-4 py-3">SKU</th><th class="px-4 py-3">Stock</th><th class="px-4 py-3">Adjust stock</th></tr></thead><tbody class="divide-y divide-line">
@forelse($rows as $r)
<tr><td class="px-4 py-3"><a href="{{ route('admin.products.edit', $r['product']) }}" class="font-medium hover:underline">{{ $r['label'] }}</a></td><td class="px-4 py-3 text-xs">{{ $r['sku'] ?: '—' }}</td>
<td class="px-4 py-3">@if(!$r['managed'])<span class="text-muted">Not tracked</span>@else<b class="{{ $r['qty'] <= 0 ? 'text-red-600' : ($r['qty'] <= $r['threshold'] ? 'text-amber-600' : '') }}">{{ $r['qty'] }}</b> @if($r['qty'] <= 0)<span class="badge bg-red-50 text-red-600">Out</span>@elseif($r['qty'] <= $r['threshold'])<span class="badge bg-amber-50 text-amber-700">Low</span>@endif @endif</td>
<td class="px-4 py-3"><form method="post" action="{{ route('admin.inventory.adjust') }}" class="flex gap-1.5">@csrf<input type="hidden" name="product_id" value="{{ $r['product']->id }}"><input type="hidden" name="variant_id" value="{{ $r['variant']?->id }}">
<select name="mode" class="rounded-lg border border-line py-1.5 pl-2 pr-6 text-xs"><option value="add">Add (+/-)</option><option value="set">Set to</option></select><input type="number" name="quantity" class="field !w-20 !py-1.5" required placeholder="qty"><input name="note" class="field !w-36 !py-1.5" placeholder="Note"><button class="btn btn-primary btn-sm">Save</button></form></td></tr>
@empty<tr><td colspan="4" class="px-4 py-14 text-center text-muted">No items.</td></tr>@endforelse</tbody></table></div>
<p class="mt-2 text-xs text-muted">“Add” accepts negative numbers (e.g. -3) to remove stock. Every change is logged.</p>
<div class="mt-4">{{ $rows->links() }}</div>
@endsection
