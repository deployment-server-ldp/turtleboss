@extends('admin.layouts.admin')
@section('title', 'Wishlist Analytics')
@section('content')
<div class="card p-4 mb-4"><p class="text-xs text-muted">Total wishlisted items</p><p class="text-2xl font-bold">{{ $total }}</p></div>
<div class="card overflow-x-auto"><table class="w-full text-left"><thead class="border-b border-line bg-soft text-xs uppercase text-muted"><tr><th class="px-4 py-3">Product</th><th class="px-4 py-3">Wishlisted by</th><th class="px-4 py-3">Stock</th></tr></thead><tbody class="divide-y divide-line">
@forelse($top as $p)<tr><td class="px-4 py-3"><a href="{{ route('admin.products.edit', $p) }}" class="font-medium text-brand">{{ $p->name }}</a></td><td class="px-4 py-3 font-semibold">{{ $p->wishlist_items__count }}</td><td class="px-4 py-3">{{ $p->in_stock ? 'In stock' : 'Out of stock' }}</td></tr>@empty<tr><td colspan="3" class="px-4 py-12 text-center text-muted">No wishlist activity yet.</td></tr>@endforelse</tbody></table></div>
@endsection
