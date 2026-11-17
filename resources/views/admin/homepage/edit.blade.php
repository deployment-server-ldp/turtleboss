@extends('admin.layouts.admin')
@section('title', 'Edit section: ' . ($types[$s->type] ?? $s->type))
@php $cfg = $s->config ?? []; @endphp
@section('content')
<form method="post" enctype="multipart/form-data" action="{{ route('admin.homepage.update', $s) }}" class="mx-auto max-w-3xl space-y-5">@csrf @method('PUT')
    <div class="card space-y-4 p-5 sm:p-6">
        <label class="flex items-center gap-3"><input type="checkbox" name="is_active" value="1" @checked($s->is_active) class="h-4 w-4 rounded border-line"><span class="font-medium">Section enabled</span></label>
        <div><label class="label">Heading</label><input name="title" value="{{ old('title', $s->title) }}" class="field"></div>
        <div><label class="label">Sub-heading</label><input name="subtitle" value="{{ old('subtitle', $s->subtitle) }}" class="field"></div>
    </div>
    @if($s->type === 'products')
        <div class="card space-y-4 p-5 sm:p-6" x-data="{ src: '{{ $cfg['source'] ?? 'featured' }}' }">
            <div class="grid gap-4 sm:grid-cols-2"><div><label class="label">Show</label><select name="source" x-model="src" class="field">@foreach(['featured' => 'Featured products', 'bestseller' => 'Best sellers', 'new' => 'New arrivals', 'trending' => 'Trending (most viewed)', 'category' => 'From a category', 'manual' => 'Hand-picked products'] as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div><div><label class="label">How many</label><input type="number" min="2" max="24" name="limit" value="{{ $cfg['limit'] ?? 8 }}" class="field"></div></div>
            <div x-show="src === 'category'"><label class="label">Category</label><select name="category_id" class="field">@foreach($categories as $id => $n)<option value="{{ $id }}" @selected(($cfg['category_id'] ?? null) == $id)>{{ $n }}</option>@endforeach</select></div>
            <div x-show="src === 'manual'"><label class="label">Products</label><select name="product_ids[]" multiple size="8" class="field">@foreach($products as $id => $n)<option value="{{ $id }}" @selected(in_array($id, $cfg['product_ids'] ?? []))>{{ $n }}</option>@endforeach</select></div>
            <div class="grid gap-4 sm:grid-cols-2"><div><label class="label">“View all” button text</label><input name="cta_text" value="{{ $cfg['cta_text'] ?? '' }}" class="field"></div><div><label class="label">“View all” URL</label><input name="cta_url" value="{{ $cfg['cta_url'] ?? '' }}" class="field" placeholder="/shop"></div></div>
            <p class="text-xs text-muted">Products flagged on the product form (Featured / Best seller / New / Trending) feed these lists.</p>
        </div>
    @elseif($s->type === 'testimonials')
        <div class="card p-5"><label class="label">Number of reviews to show</label><input type="number" name="limit" min="1" max="12" value="{{ $cfg['limit'] ?? 6 }}" class="field max-w-[140px]"><p class="mt-2 text-xs text-muted">Shows approved reviews rated 4★+ only. Hidden automatically while there are none.</p></div>
    @elseif($s->type === 'why_choose')
        <div class="card space-y-3 p-5 sm:p-6" x-data="{ items: @js($cfg['items'] ?? []) }"><p class="font-semibold">Feature blocks</p>
            <template x-for="(it, i) in items" :key="i"><div class="grid gap-2 rounded-xl border border-line p-3 sm:grid-cols-[110px_1fr_1.5fr_auto]"><select :name="`items[${i}][icon]`" x-model="it.icon" class="field !py-2"><option value="badge">Badge</option><option value="smile">Smile</option><option value="lock">Lock</option><option value="truck">Truck</option></select><input :name="`items[${i}][title]`" x-model="it.title" class="field !py-2" placeholder="Title"><input :name="`items[${i}][text]`" x-model="it.text" class="field !py-2" placeholder="Description"><button type="button" class="text-red-600" @click="items.splice(i, 1)">✕</button></div></template>
            <button type="button" class="btn btn-outline btn-sm" @click="items.push({icon: 'badge', title: '', text: ''})">+ Add block</button></div>
    @elseif($s->type === 'rich_text')
        <div class="card p-5"><div x-data="richText" class="overflow-hidden rounded-xl border border-line"><div class="flex gap-1 border-b border-line bg-soft p-1.5 text-sm"><button type="button" @click="cmd('bold')" class="rounded px-2.5 py-1 hover:bg-white">B</button><button type="button" @click="cmd('italic')" class="rounded px-2.5 py-1 hover:bg-white">I</button><button type="button" @click="cmd('formatBlock','h3')" class="rounded px-2.5 py-1 hover:bg-white">H3</button><button type="button" @click="link()" class="rounded px-2.5 py-1 hover:bg-white">Link</button></div><div x-ref="area" contenteditable class="prose-tb min-h-[160px] p-4 outline-none" @input="sync()"></div><textarea x-ref="input" name="html" class="hidden">{{ $cfg['html'] ?? '' }}</textarea></div></div>
    @elseif($s->type === 'banners')
        <div class="card space-y-4 p-5 sm:p-6" x-data="{ items: @js(array_map(fn ($b) => $b + ['i' => 0], $cfg['items'] ?? [])) }"><p class="font-semibold">Promotional banners (1–2 recommended)</p>
            <template x-for="(b, i) in items" :key="i"><div class="space-y-2 rounded-xl border border-line p-3">
                <input type="hidden" :name="`banners[${i}][existing_image]`" :value="b.image"><div class="flex items-center gap-3"><template x-if="b.image"><img :src="'{{ asset('storage') }}/' + b.image" class="h-16 w-28 rounded object-cover" alt=""></template><input type="file" :name="`banners[${i}][image]`" accept="image/*" class="text-sm"></div>
                <div class="grid gap-2 sm:grid-cols-2"><input :name="`banners[${i}][heading]`" x-model="b.heading" class="field !py-2" placeholder="Heading"><input :name="`banners[${i}][text]`" x-model="b.text" class="field !py-2" placeholder="Text"><input :name="`banners[${i}][cta_text]`" x-model="b.cta_text" class="field !py-2" placeholder="Button text"><input :name="`banners[${i}][cta_url]`" x-model="b.cta_url" class="field !py-2" placeholder="Button URL (/shop)"><input :name="`banners[${i}][alt]`" x-model="b.alt" class="field !py-2 sm:col-span-2" placeholder="Image ALT text"></div>
                <label class="flex items-center gap-2 text-xs text-red-600"><input type="checkbox" :name="`banners[${i}][remove]`" value="1"> Remove this banner</label></div></template>
            <button type="button" class="btn btn-outline btn-sm" @click="items.push({image: '', heading: '', text: '', cta_text: '', cta_url: '', alt: ''})">+ Add banner</button></div>
    @endif
    <div class="flex gap-3"><button class="btn btn-primary">Save section</button><a href="{{ route('admin.homepage.index') }}" class="btn btn-ghost">Back</a></div>
</form>
@endsection
