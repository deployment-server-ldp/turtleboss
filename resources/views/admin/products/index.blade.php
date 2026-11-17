@extends('admin.layouts.admin')
@section('title', 'Products')
@section('content')
<div x-data="{ sel: [], all: false, action: '' }">
<div class="mb-4 flex flex-wrap items-center gap-2">
    <form class="flex flex-wrap gap-2" method="get">
        <input name="q" value="{{ request('q') }}" placeholder="Search name, SKU, brand…" class="field !w-56 !py-2">
        <select name="category" class="field !w-44 !py-2"><option value="">All categories</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>@endforeach</select>
        <select name="status" class="field !w-32 !py-2"><option value="">Any status</option><option value="active" @selected(request('status') === 'active')>Published</option><option value="draft" @selected(request('status') === 'draft')>Draft</option></select>
        <select name="stock" class="field !w-36 !py-2"><option value="">Any stock</option><option value="low" @selected(request('stock') === 'low')>Low stock</option><option value="out" @selected(request('stock') === 'out')>Out of stock</option></select>
        <button class="btn btn-outline btn-sm">Filter</button>
    </form>
    <div class="ml-auto flex gap-2">
        <details class="relative"><summary class="btn btn-outline btn-sm cursor-pointer list-none">CSV ▾</summary>
            <div class="absolute right-0 z-10 mt-1 w-72 space-y-2 rounded-xl border border-line bg-white p-3 shadow-lg">
                <a href="{{ route('admin.products.export') }}" class="block rounded px-2 py-1.5 hover:bg-soft">⬇ Export all products</a>
                <a href="{{ route('admin.products.sample') }}" class="block rounded px-2 py-1.5 hover:bg-soft">⬇ Download sample CSV</a>
                <form method="post" action="{{ route('admin.products.import') }}" enctype="multipart/form-data" class="border-t border-line pt-2">@csrf<input type="file" name="file" accept=".csv,text/csv" required class="block w-full text-xs"><button class="btn btn-primary btn-sm mt-2 w-full">Import CSV</button><p class="mt-1 text-[11px] text-muted">Matches by id or SKU. Requires name & price columns. Variants & images are managed in the product form.</p></form>
            </div></details>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm">+ Add product</a>
    </div>
</div>
@if(session('import_errors'))<div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">@foreach(session('import_errors') as $e)<p>{{ $e }}</p>@endforeach</div>@endif

<form method="post" action="{{ route('admin.products.bulk') }}" onsubmit="return action.value !== 'delete' || confirm('Delete selected products?')">@csrf
    <template x-for="id in sel"><input type="hidden" name="ids[]" :value="id"></template>
    <div x-show="sel.length" x-cloak class="mb-3 flex flex-wrap items-center gap-2 rounded-xl bg-navy p-3 text-white">
        <span class="text-sm" x-text="sel.length + ' selected'"></span>
        <select name="action" x-model="action" class="rounded-lg border-0 py-1.5 pl-2 pr-7 text-sm text-ink"><option value="">Bulk action…</option><option value="publish">Publish</option><option value="unpublish">Unpublish</option><option value="feature">Mark featured</option><option value="unfeature">Unmark featured</option><option value="bestseller">Mark best seller</option><option value="new">Mark new arrival</option><option value="category">Add to category</option><option value="price">Update price</option><option value="delete">Delete</option></select>
        <select name="category_id" x-show="action === 'category'" class="rounded-lg border-0 py-1.5 text-sm text-ink">@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
        <template x-if="action === 'price'"><span class="flex gap-2"><select name="mode" class="rounded-lg border-0 py-1.5 text-sm text-ink"><option value="percent">± % change</option><option value="fixed">± Rs. change</option><option value="set">Set price to</option></select><input name="value" type="number" step="0.01" class="w-28 rounded-lg border-0 py-1.5 text-sm text-ink" placeholder="e.g. 10 or -5"></span></template>
        <button class="btn btn-sm bg-white !text-navy" :disabled="!action">Apply</button>
    </div>
    <div class="card overflow-x-auto"><table class="w-full min-w-[820px] text-left">
        <thead class="border-b border-line bg-soft text-xs uppercase text-muted"><tr><th class="w-10 px-4 py-3"><input type="checkbox" x-model="all" @change="sel = all ? {{ $products->pluck('id') }} : []"></th><th class="px-2 py-3">Product</th><th class="px-3 py-3">SKU</th><th class="px-3 py-3">Price</th><th class="px-3 py-3">Stock</th><th class="px-3 py-3">Flags</th><th class="px-3 py-3">Status</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
        <tbody class="divide-y divide-line">
        @forelse($products as $p)
            <tr class="hover:bg-soft/60"><td class="px-4 py-3"><input type="checkbox" value="{{ $p->id }}" x-model.number="sel"></td>
                <td class="px-2 py-3"><a href="{{ route('admin.products.edit', $p) }}" class="flex items-center gap-3"><img src="{{ $p->image_url }}" class="h-11 w-11 rounded-lg bg-soft object-cover" alt=""><span><span class="block max-w-[260px] truncate font-medium">{{ $p->name }}</span><span class="text-xs text-muted">{{ $p->category?->name }} @if($p->is_demo)<span class="badge bg-amber-100 text-amber-700">DEMO</span>@endif</span></span></a></td>
                <td class="px-3 py-3 text-xs">{{ $p->sku ?: '—' }}</td>
                <td class="px-3 py-3">@if($p->on_sale)<span class="font-semibold text-sale">{{ money($p->final_price) }}</span> <span class="text-xs text-muted line-through">{{ money($p->regular_price) }}</span>@else{{ money($p->final_price) }}@endif</td>
                <td class="px-3 py-3">@if($p->has_variants){{ $p->total_stock }} <span class="text-xs text-muted">({{ $p->variants->count() }} var.)</span>@elseif($p->manage_stock)<span class="{{ $p->stock_qty <= 0 ? 'text-red-600' : ($p->is_low_stock ? 'text-amber-600' : '') }}">{{ $p->stock_qty }}</span>@else<span class="text-muted">{{ $p->stock_status === 'in_stock' ? 'In stock' : 'Out' }}</span>@endif</td>
                <td class="px-3 py-3"><div class="flex gap-1">@foreach(['is_featured' => ['★', 'Featured'], 'is_bestseller' => ['B', 'Best seller'], 'is_new' => ['N', 'New arrival'], 'is_trending' => ['T', 'Trending']] as $f => [$ic, $tt])<button form="tg{{ $p->id }}{{ $f }}" title="{{ $tt }}" class="grid h-6 w-6 place-items-center rounded text-[11px] font-bold {{ $p->$f ? 'bg-navy text-white' : 'bg-gray-100 text-gray-400' }}">{{ $ic }}</button>@endforeach</div></td>
                <td class="px-3 py-3"><button form="tg{{ $p->id }}is_active" class="badge {{ $p->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $p->is_active ? 'Published' : 'Draft' }}</button></td>
                <td class="whitespace-nowrap px-4 py-3 text-right"><a href="{{ route('admin.products.edit', $p) }}" class="font-medium text-brand">Edit</a><button form="dup{{ $p->id }}" class="ml-3 text-muted hover:text-navy">Duplicate</button><a href="{{ $p->url }}" target="_blank" class="ml-3 text-muted hover:text-navy">View</a><button form="del{{ $p->id }}" class="ml-3 text-red-600" onclick="return confirm('Delete this product?')">Delete</button></td></tr>
        @empty<tr><td colspan="8" class="px-4 py-14 text-center text-muted">No products yet. <a href="{{ route('admin.products.create') }}" class="text-brand underline">Add your first product</a> or import a CSV.</td></tr>@endforelse
        </tbody></table></div>
</form>
@foreach($products as $p)
    <form id="dup{{ $p->id }}" method="post" action="{{ route('admin.products.duplicate', $p) }}" class="hidden">@csrf</form>
    <form id="del{{ $p->id }}" method="post" action="{{ route('admin.products.destroy', $p) }}" class="hidden">@csrf @method('DELETE')</form>
    @foreach(['is_active', 'is_featured', 'is_bestseller', 'is_new', 'is_trending'] as $f)<form id="tg{{ $p->id }}{{ $f }}" method="post" action="{{ route('admin.products.toggle', $p) }}" class="hidden">@csrf<input type="hidden" name="field" value="{{ $f }}"></form>@endforeach
@endforeach
<div class="mt-4">{{ $products->links() }}</div>
</div>
@endsection
