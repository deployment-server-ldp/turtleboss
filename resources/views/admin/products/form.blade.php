@extends('admin.layouts.admin')
@section('title', $p->exists ? 'Edit product' : 'New product')
@php
    $o = fn ($k, $d = null) => old($k, $p->exists ? $p->$k : $d);
    $oldVariants = old('variants');
@endphp
@section('content')
<form method="post" enctype="multipart/form-data" action="{{ $p->exists ? route('admin.products.update', $p) : route('admin.products.store') }}" class="mx-auto max-w-6xl" x-data="{ tab: 'general' }">
    @csrf @if($p->exists) @method('PUT') @endif
    <div class="sticky top-16 z-10 -mx-4 mb-5 flex flex-wrap items-center gap-2 border-b border-line bg-soft/95 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6">
        <div class="no-scrollbar flex gap-1 overflow-x-auto">@foreach(['general' => 'General', 'pricing' => 'Pricing & Stock', 'media' => 'Images', 'variants' => 'Variations', 'specs' => 'Specs & Shipping', 'links' => 'Related', 'seo' => 'SEO'] as $k => $l)<button type="button" @click="tab = '{{ $k }}'" class="shrink-0 rounded-lg px-3.5 py-2 text-sm font-medium" :class="tab === '{{ $k }}' ? 'bg-navy text-white' : 'hover:bg-white'">{{ $l }}</button>@endforeach</div>
        <div class="ml-auto flex gap-2"><a href="{{ route('admin.products.index') }}" class="btn btn-ghost btn-sm">Cancel</a><button class="btn btn-primary btn-sm">Save product</button></div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_300px]">
        <div class="space-y-6">
            {{-- GENERAL --}}
            <div x-show="tab === 'general'" class="card space-y-5 p-5 sm:p-6">
                <div><label class="label">Product name *</label><input name="name" value="{{ $o('name') }}" class="field" required></div>
                <div class="grid gap-4 sm:grid-cols-2"><div><label class="label">URL slug</label><input name="slug" value="{{ $o('slug') }}" class="field" placeholder="auto-generated"></div><div><label class="label">Brand</label><input name="brand" value="{{ $o('brand') }}" class="field"></div></div>
                <div class="grid gap-4 sm:grid-cols-2"><div><label class="label">SKU</label><input name="sku" value="{{ $o('sku') }}" class="field"></div><div><label class="label">Barcode (EAN/UPC)</label><input name="barcode" value="{{ $o('barcode') }}" class="field"></div></div>
                <div><label class="label">Short description</label><textarea name="short_description" rows="3" class="field">{{ $o('short_description') }}</textarea></div>
                <div><label class="label">Full description</label>
                    <div x-data="richText" class="overflow-hidden rounded-xl border border-line"><div class="flex flex-wrap gap-1 border-b border-line bg-soft p-1.5 text-sm">@foreach([['bold', 'B'], ['italic', 'I'], ['insertUnorderedList', '• List'], ['insertOrderedList', '1. List'], ['removeFormat', 'Clear']] as [$c, $l])<button type="button" @click="cmd('{{ $c }}')" class="rounded px-2.5 py-1 hover:bg-white">{{ $l }}</button>@endforeach<button type="button" @click="cmd('formatBlock','h3')" class="rounded px-2.5 py-1 hover:bg-white">H3</button><button type="button" @click="link()" class="rounded px-2.5 py-1 hover:bg-white">Link</button></div>
                        <div x-ref="area" contenteditable="true" @input="sync()" @blur="sync()" class="prose-tb min-h-[220px] max-w-none p-4 outline-none"></div><textarea x-ref="input" name="description" class="hidden">{{ $o('description') }}</textarea></div></div>
                <div><label class="label">Product video URL (YouTube/Vimeo/MP4)</label><input name="video_url" value="{{ $o('video_url') }}" class="field" placeholder="https://"></div>
            </div>

            {{-- PRICING --}}
            <div x-show="tab === 'pricing'" x-cloak class="card space-y-5 p-5 sm:p-6">
                <div class="grid gap-4 sm:grid-cols-3"><div><label class="label">Regular price (Rs.) *</label><input type="number" step="0.01" min="0" name="price" value="{{ $o('price') }}" class="field" required></div><div><label class="label">Sale price (Rs.)</label><input type="number" step="0.01" min="0" name="sale_price" value="{{ $o('sale_price') }}" class="field"></div><div><label class="label">Cost price <span class="font-normal text-muted">(admin only)</span></label><input type="number" step="0.01" min="0" name="cost_price" value="{{ $o('cost_price') }}" class="field"></div></div>
                <p class="text-xs text-muted">When variations are used, each variation can override price/sale price/stock. Empty variation price = use these.</p>
                <hr class="border-line">
                <label class="flex items-center gap-3"><input type="hidden" name="manage_stock" value="0"><input type="checkbox" name="manage_stock" value="1" @checked($o('manage_stock', true)) class="h-4 w-4 rounded border-line"><span class="font-medium">Track stock quantity for this product</span></label>
                <div class="grid gap-4 sm:grid-cols-3"><div><label class="label">Stock quantity</label><input type="number" min="0" name="stock_qty" value="{{ $o('stock_qty', 0) }}" class="field"></div><div><label class="label">Low-stock alert at</label><input type="number" min="0" name="low_stock_threshold" value="{{ $o('low_stock_threshold', 5) }}" class="field"></div>
                    <div><label class="label">Stock status</label><select name="stock_status" class="field">@foreach(['in_stock' => 'In stock', 'out_of_stock' => 'Out of stock', 'backorder' => 'Allow backorders'] as $k => $l)<option value="{{ $k }}" @selected($o('stock_status', 'in_stock') === $k)>{{ $l }}</option>@endforeach</select></div></div>
                <p class="text-xs text-muted">With tracking on, status follows quantity automatically (unless backorders are allowed). For products with variations, stock is managed per variation.</p>
                @if($logs->isNotEmpty())<div><p class="label">Recent stock history</p><ul class="divide-y divide-line rounded-xl border border-line text-xs">@foreach($logs as $l)<li class="flex justify-between gap-3 px-3 py-2"><span>{{ $l->created_at->format('d M H:i') }} · {{ ucfirst($l->reason) }} {{ $l->note }} @if($l->variant)<i>({{ $l->variant->label }})</i>@endif</span><b class="{{ $l->change < 0 ? 'text-red-600' : 'text-green-700' }}">{{ $l->change > 0 ? '+' : '' }}{{ $l->change }} → {{ $l->qty_after }}</b></li>@endforeach</ul></div>@endif
            </div>

            {{-- IMAGES --}}
            <div x-show="tab === 'media'" x-cloak class="card space-y-5 p-5 sm:p-6">
                <div><label class="label">Add images (multiple, JPG/PNG/WebP — converted to WebP automatically)</label><input type="file" name="new_images[]" multiple accept="image/jpeg,image/png,image/webp" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-navy file:px-4 file:py-2 file:text-white"></div>
                @if($p->exists && $p->allImages->whereNull('variant_id')->isNotEmpty())
                    <p class="text-xs text-muted">Order controls the gallery: the lowest number is the main image.</p>
                    <div class="grid gap-3 sm:grid-cols-2">@foreach($p->allImages->whereNull('variant_id') as $im)
                        <div class="flex gap-3 rounded-xl border border-line p-3"><img src="{{ $im->url }}" class="h-24 w-24 rounded-lg bg-soft object-cover" alt="">
                            <div class="min-w-0 flex-1 space-y-1.5 text-xs"><div class="flex gap-2"><label class="w-20">Order<input type="number" name="image_order[{{ $im->id }}]" value="{{ $im->sort_order }}" class="field !px-2 !py-1.5"></label><label class="flex-1">ALT text<input name="image_alt[{{ $im->id }}]" value="{{ $im->alt }}" class="field !px-2 !py-1.5"></label></div>
                                <label class="block">Replace<input type="file" name="replace_image[{{ $im->id }}]" accept="image/*" class="block w-full text-[11px]"></label>
                                <label class="flex items-center gap-1.5 text-red-600"><input type="checkbox" name="delete_images[]" value="{{ $im->id }}"> Delete</label></div></div>@endforeach</div>
                @else<p class="text-sm text-muted">No images yet.</p>@endif
            </div>

            {{-- VARIANTS --}}
            <div x-show="tab === 'variants'" x-cloak class="card space-y-5 p-5 sm:p-6" x-data="variantEditor(@js($attrs->map(fn ($a) => ['id' => $a->id, 'name' => $a->name, 'values' => $a->values->map(fn ($v) => ['id' => $v->id, 'value' => $v->value])->values()])), @js($oldVariants ? collect($oldVariants)->map(fn ($v) => $v + ['values' => $v['values'] ?? [], 'image' => null])->values() : $variantRows), false)">
                <div><p class="font-semibold">Product variations</p><p class="text-xs text-muted">Tick the option values this product comes in, then generate the combinations. Each combination has its own SKU, price, sale price, stock and image. Manage attributes under Catalog → Product Attributes.</p></div>
                <div class="space-y-3">@foreach($attrs as $a)<div class="rounded-xl border border-line p-3"><p class="mb-2 text-sm font-semibold">{{ $a->name }}</p><div class="flex flex-wrap gap-2">@foreach($a->values as $v)<label class="cursor-pointer"><input type="checkbox" class="peer sr-only" :checked="has({{ $a->id }}, {{ $v->id }})" @change="toggle({{ $a->id }}, {{ $v->id }})"><span class="flex items-center gap-1.5 rounded-lg border border-line px-3 py-1.5 text-sm peer-checked:border-navy peer-checked:bg-navy peer-checked:text-white">@if($v->color_hex)<i class="h-3 w-3 rounded-full border border-white/50" style="background:{{ $v->color_hex }}"></i>@endif{{ $v->value }}</span></label>@endforeach</div></div>@endforeach</div>
                <button type="button" class="btn btn-outline btn-sm" @click="generate()">Generate combinations</button>
                @error('variants')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                <div class="space-y-3">
                    <template x-for="(r, i) in rows" :key="i">
                        <div class="rounded-xl border border-line p-3">
                            <div class="mb-2 flex items-center justify-between"><p class="font-semibold" x-text="label(r)"></p><button type="button" class="text-xs text-red-600" @click="remove(i)">Remove</button></div>
                            <input type="hidden" :name="`variants[${i}][id]`" :value="r.id">
                            <template x-for="vid in r.values"><input type="hidden" :name="`variants[${i}][values][]`" :value="vid"></template>
                            <div class="grid gap-2 sm:grid-cols-5">
                                <label class="text-xs">SKU<input :name="`variants[${i}][sku]`" x-model="r.sku" class="field !py-1.5"></label>
                                <label class="text-xs">Price<input type="number" step="0.01" :name="`variants[${i}][price]`" x-model="r.price" class="field !py-1.5" placeholder="default"></label>
                                <label class="text-xs">Sale price<input type="number" step="0.01" :name="`variants[${i}][sale_price]`" x-model="r.sale_price" class="field !py-1.5"></label>
                                <label class="text-xs">Stock<input type="number" min="0" :name="`variants[${i}][stock_qty]`" x-model="r.stock_qty" class="field !py-1.5"></label>
                                <label class="flex items-end gap-2 pb-2 text-xs"><input type="checkbox" value="1" :name="`variants[${i}][is_active]`" x-model="r.is_active" class="rounded border-line"> Available</label>
                            </div>
                            <div class="mt-2 flex items-center gap-3 text-xs"><template x-if="r.image"><img :src="r.image" class="h-10 w-10 rounded object-cover" alt=""></template><label>Variation image <input type="file" accept="image/*" :name="`variants[${i}][image]`" class="text-[11px]"></label><label x-show="r.image" class="text-red-600"><input type="checkbox" value="1" :name="`variants[${i}][remove_image]`"> remove image</label></div>
                        </div>
                    </template>
                    <p x-show="!rows.length" class="text-sm text-muted">No variations — this is a simple product.</p>
                </div>
            </div>

            {{-- SPECS & SHIPPING --}}
            <div x-show="tab === 'specs'" x-cloak class="space-y-6">
                <div class="card space-y-4 p-5 sm:p-6" x-data="{ rows: @js($p->specs->map(fn ($s) => ['n' => $s->name, 'v' => $s->value])->values()->all() ?: [['n' => '', 'v' => '']]) }">
                    <p class="font-semibold">Specifications / attributes</p>
                    <template x-for="(r, i) in rows" :key="i"><div class="flex gap-2"><input :name="`specs[name][${i}]`" x-model="r.n" placeholder="e.g. Material" class="field"><input :name="`specs[value][${i}]`" x-model="r.v" placeholder="e.g. Stainless steel" class="field"><button type="button" class="px-2 text-red-600" @click="rows.splice(i, 1)">✕</button></div></template>
                    <button type="button" class="btn btn-outline btn-sm" @click="rows.push({n: '', v: ''})">+ Add row</button>
                </div>
                <div class="card space-y-4 p-5 sm:p-6"><p class="font-semibold">Shipping</p>
                    <div class="grid gap-4 sm:grid-cols-4"><label class="text-sm">Weight (kg)<input type="number" step="0.01" name="weight" value="{{ $o('weight') }}" class="field"></label><label class="text-sm">Length (cm)<input type="number" step="0.01" name="length" value="{{ $o('length') }}" class="field"></label><label class="text-sm">Width (cm)<input type="number" step="0.01" name="width" value="{{ $o('width') }}" class="field"></label><label class="text-sm">Height (cm)<input type="number" step="0.01" name="height" value="{{ $o('height') }}" class="field"></label></div>
                    <div><label class="label">Shipping information (shown on product page; empty = store default)</label><textarea name="shipping_info" rows="2" class="field">{{ $o('shipping_info') }}</textarea></div></div>
            </div>

            {{-- RELATED --}}
            <div x-show="tab === 'links'" x-cloak class="card space-y-5 p-5 sm:p-6">
                @foreach(['related_ids' => ['Related products', $related], 'cross_ids' => ['Cross-sell products (suggested in cart)', $cross], 'upsell_ids' => ['Upsell products (“You may also like”)', $upsell]] as $k => [$l, $sel])
                    <div><label class="label">{{ $l }}</label><select name="{{ $k }}[]" multiple size="6" class="field">@foreach($others as $id => $nm)<option value="{{ $id }}" @selected(in_array($id, old($k, $sel)))>{{ $nm }}</option>@endforeach</select></div>
                @endforeach
                <p class="text-xs text-muted">Hold Ctrl / Cmd to select multiple. If no related products are chosen, products from the same category are shown automatically.</p>
            </div>

            <div x-show="tab === 'seo'" x-cloak>@include('admin.partials.seo-fields', ['model' => $p])
                <script>document.querySelector('[x-show="tab === \'seo\'"] details')?.setAttribute('open', '')</script></div>
        </div>

        {{-- SIDEBAR --}}
        <aside class="space-y-6">
            <div class="card space-y-3 p-5">
                <p class="font-semibold">Visibility</p>
                @foreach(['is_active' => 'Published', 'is_featured' => 'Featured', 'is_bestseller' => 'Best seller', 'is_new' => 'New arrival', 'is_trending' => 'Trending'] as $f => $l)
                    <label class="flex items-center gap-3"><input type="hidden" name="{{ $f }}" value="0"><input type="checkbox" name="{{ $f }}" value="1" @checked($o($f, $f === 'is_active')) class="h-4 w-4 rounded border-line"> {{ $l }}</label>
                @endforeach
                @if($p->exists)<a href="{{ $p->url }}" target="_blank" class="block pt-1 text-xs text-brand underline">View on storefront ↗</a>@endif
            </div>
            <div class="card p-5"><p class="mb-2 font-semibold">Categories</p>
                @php $selCats = old('category_ids', $p->exists ? $p->categories->pluck('id')->all() : []); @endphp
                <div class="max-h-60 space-y-1.5 overflow-y-auto">@foreach($categories as $c)<label class="flex items-center gap-2" style="padding-left: {{ $c->parent_id ? 18 : 0 }}px"><input type="checkbox" name="category_ids[]" value="{{ $c->id }}" @checked(in_array($c->id, $selCats)) class="rounded border-line"> {{ $c->name }}</label>@endforeach</div>
                <label class="label mt-3 !text-xs">Primary category</label><select name="primary_category" class="field !py-2"><option value="">Auto (first selected)</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected((int) old('primary_category', $p->category_id) === $c->id)>{{ $c->name }}</option>@endforeach</select>
            </div>
        </aside>
    </div>
</form>
@endsection
