@extends('layouts.app')
@section('content')
<div class="container-x py-5 sm:py-8" x-data="productPage({{ \Illuminate\Support\Js::from($config) }})" @keydown.escape.window="lightbox = false" @keydown.arrow-right.window="lightbox && next()" @keydown.arrow-left.window="lightbox && prev()">
    @include('partials.breadcrumbs')

    <div class="grid gap-6 lg:grid-cols-[1.15fr_1fr] lg:gap-12">
        {{-- GALLERY --}}
        <div class="flex flex-col gap-3 lg:flex-row-reverse lg:items-start lg:gap-4">
            <div class="relative flex-1 overflow-hidden rounded-[18px] bg-soft" :class="zoom ? 'cursor-zoom-out' : 'cursor-zoom-in'"
                 @mouseenter="zoom = window.innerWidth >= 1024" @mouseleave="zoom = false" @mousemove="zoom && move($event)" @click="lightbox = true; $store.ui.lock(true)">
                <div class="aspect-square">
                    <template x-for="(g, i) in gallery" :key="g.url + i">
                        <img x-show="main === i" :src="g.url" :alt="g.alt || '{{ addslashes($p->name) }}'" width="900" height="900"
                             class="absolute inset-0 h-full w-full object-cover transition duration-300" :style="zoom && main === i ? `transform: scale(2); transform-origin: ${zx}% ${zy}%` : ''"
                             x-transition.opacity.duration.250ms :loading="i ? 'lazy' : 'eager'" :fetchpriority="i ? 'low' : 'high'">
                    </template>
                    <img x-show="!gallery.length" src="{{ img(null) }}" alt="" class="h-full w-full object-cover">
                </div>
                <template x-if="onSale"><span class="badge absolute left-3 top-3 bg-sale text-white" x-text="'-' + discount + '%'"></span></template>
                <template x-if="gallery.length > 1">
                    <div class="lg:hidden"><button @click.stop="prev()" class="absolute left-2 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-full bg-white/90 shadow" aria-label="Previous image">‹</button><button @click.stop="next()" class="absolute right-2 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-full bg-white/90 shadow" aria-label="Next image">›</button></div>
                </template>
            </div>
            {{-- thumbs: vertical on desktop (left), horizontal on mobile --}}
            <div class="no-scrollbar flex gap-2.5 overflow-x-auto lg:max-h-[640px] lg:w-[84px] lg:flex-col lg:overflow-y-auto lg:overflow-x-hidden" x-show="gallery.length > 1">
                <template x-for="(g, i) in gallery" :key="'t' + g.url + i">
                    <button type="button" @click="main = i" @mouseenter="window.innerWidth >= 1024 && (main = i)" class="aspect-square w-[68px] shrink-0 overflow-hidden rounded-xl border-2 bg-soft transition lg:w-full" :class="main === i ? 'border-navy' : 'border-transparent hover:border-line'" :aria-label="'Show image ' + (i + 1)">
                        <img :src="g.url" alt="" class="h-full w-full object-cover" loading="lazy" width="84" height="84">
                    </button>
                </template>
            </div>
        </div>

        {{-- INFO --}}
        <div>
            @if($p->brand)<p class="text-xs font-semibold uppercase tracking-wider text-muted">{{ $p->brand }}</p>@endif
            <h1 class="mt-1 text-2xl leading-tight sm:text-3xl">{{ $p->name }}</h1>
            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted">
                @if($p->rating_count)<a href="#reviews" class="flex items-center gap-1.5"><x-stars :rating="$p->rating_avg"/><span class="font-medium text-navy">{{ number_format($p->rating_avg, 1) }}</span> · {{ $p->rating_count }} {{ \Illuminate\Support\Str::plural('review', $p->rating_count) }}</a>
                @else<a href="#reviews" class="hover:underline">No reviews yet</a>@endif
                <span>SKU: <span class="text-navy" x-text="sku || '—'">{{ $p->sku ?: '—' }}</span></span>
            </div>
            @include('product._price')
            @if($p->short_description)<p class="mt-4 text-[15px] leading-7 text-[#444]">{{ $p->short_description }}</p>@endif
            @include('product._buybox')

            <ul class="mt-6 divide-y divide-line rounded-2xl border border-line text-sm">
                <li class="flex gap-3 p-4"><svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h11v9H3zM14 9h4l3 3v3h-7"/><circle cx="7" cy="18" r="1.500"/><circle cx="17" cy="18" r="1.500"/></svg><div><p class="font-semibold">Shipping</p><p class="text-muted">{{ $p->shipping_info ?: setting('shipping_info', 'Delivered across Pakistan.') }}</p></div></li>
                <li class="flex gap-3 p-4"><svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18"/></svg><div><p class="font-semibold">Payment</p><p class="text-muted">{{ setting('payment_info', 'Cash on Delivery · Bank Transfer') }}</p></div></li>
            </ul>
        </div>
    </div>

    {{-- TABS --}}
    <section class="mt-12" x-data="{ tab: 'desc' }">
        <div class="no-scrollbar flex gap-1 overflow-x-auto border-b border-line" role="tablist">
            @foreach(['desc' => 'Description', 'specs' => 'Specifications', 'info' => 'Additional Information', 'reviews' => 'Reviews (' . $p->rating_count . ')'] as $k => $l)
                <button role="tab" @click="tab = '{{ $k }}'" :aria-selected="tab === '{{ $k }}'" class="-mb-px shrink-0 border-b-2 px-4 py-3 text-sm font-semibold transition" :class="tab === '{{ $k }}' ? 'border-navy text-navy' : 'border-transparent text-muted hover:text-navy'">{{ $l }}</button>
            @endforeach
        </div>
        <div class="py-6">
            <div x-show="tab === 'desc'" class="prose-tb max-w-3xl">{!! $p->description ?: '<p>No description available.</p>' !!}
                @if($p->video_url)<p><a href="{{ $p->video_url }}" target="_blank" rel="noopener">▶ Watch product video</a></p>@endif</div>
            <div x-show="tab === 'specs'" x-cloak>
                @if($p->specs->isNotEmpty())<table class="w-full max-w-2xl text-sm"><tbody>@foreach($p->specs as $s)<tr class="border-b border-line"><th class="w-1/3 bg-soft p-3 text-left font-medium">{{ $s->name }}</th><td class="p-3">{{ $s->value }}</td></tr>@endforeach</tbody></table>
                @else<p class="text-muted">No specifications listed.</p>@endif
            </div>
            <div x-show="tab === 'info'" x-cloak>
                <table class="w-full max-w-2xl text-sm"><tbody>
                    @foreach(array_filter(['Brand' => $p->brand, 'SKU' => $p->sku, 'Barcode' => $p->barcode, 'Weight' => $p->weight ? rtrim(rtrim($p->weight, '0'), '.') . ' kg' : null, 'Dimensions' => ($p->length && $p->width && $p->height) ? "$p->length × $p->width × $p->height cm" : null, 'Category' => $p->category?->name]) as $k => $v)
                        <tr class="border-b border-line"><th class="w-1/3 bg-soft p-3 text-left font-medium">{{ $k }}</th><td class="p-3">{{ $v }}</td></tr>
                    @endforeach
                    @foreach($config['attributes'] as $a)<tr class="border-b border-line"><th class="w-1/3 bg-soft p-3 text-left font-medium">{{ $a['name'] }}</th><td class="p-3">{{ collect($a['values'])->pluck('value')->implode(', ') }}</td></tr>@endforeach
                </tbody></table>
            </div>
            <div x-show="tab === 'reviews'" x-cloak id="reviews" x-init="if (location.hash === '#reviews') tab = 'reviews'" @hashchange.window="if (location.hash === '#reviews') tab = 'reviews'">
                <div class="grid gap-10 lg:grid-cols-[320px_1fr]">
                    <div>
                        <div class="flex items-end gap-3"><span class="text-5xl font-bold">{{ $p->rating_count ? number_format($p->rating_avg, 1) : '–' }}</span><div class="pb-1.5"><x-stars :rating="$p->rating_avg" size="w-5 h-5"/><p class="text-xs text-muted">{{ $p->rating_count }} reviews</p></div></div>
                        @if($p->rating_count)<div class="mt-4 space-y-1.5">@foreach([5,4,3,2,1] as $n)@php $c = $dist[$n] ?? 0; @endphp<div class="flex items-center gap-2 text-xs"><span class="w-3">{{ $n }}</span><div class="h-2 flex-1 overflow-hidden rounded-full bg-soft"><div class="h-full bg-amber-400" style="width: {{ $p->rating_count ? ($c / $p->rating_count) * 100 : 0 }}%"></div></div><span class="w-5 text-muted">{{ $c }}</span></div>@endforeach</div>@endif
                        <form method="post" action="{{ route('product.review', $p->slug) }}" enctype="multipart/form-data" class="mt-8 space-y-3" x-data="{ r: 0 }">
                            @csrf
                            <h3 class="text-lg">Write a review</h3>
                            <div class="flex gap-1" role="radiogroup" aria-label="Rating">@for($i = 1; $i <= 5; $i++)<button type="button" @click="r = {{ $i }}" class="text-2xl leading-none" :class="r >= {{ $i }} ? 'text-amber-400' : 'text-gray-300'" aria-label="{{ $i }} stars">★</button>@endfor</div>
                            <input type="hidden" name="rating" :value="r">
                            @error('rating')<p class="text-xs text-red-600">Please choose a star rating.</p>@enderror
                            @guest<input name="name" value="{{ old('name') }}" class="field" placeholder="Your name" required><input name="email" type="email" value="{{ old('email') }}" class="field" placeholder="Email (not shown publicly)" required>@endguest
                            <input name="title" value="{{ old('title') }}" class="field" placeholder="Review title (optional)">
                            <textarea name="body" rows="4" class="field" placeholder="Share your experience" required>{{ old('body') }}</textarea>
                            <label class="block text-xs text-muted">Photo (optional) <input type="file" name="image" accept="image/*" class="mt-1 block w-full text-xs"></label>
                            @if($errors->any())<p class="text-xs text-red-600">{{ $errors->first() }}</p>@endif
                            <button class="btn btn-primary w-full">Submit review</button>
                            <p class="text-xs text-muted">Reviews are moderated. “Verified purchase” is shown only if your email matches a past order of this product.</p>
                        </form>
                    </div>
                    <div class="space-y-5">
                        @forelse($p->approvedReviews as $r)
                            <article class="border-b border-line pb-5">
                                <div class="flex items-center justify-between"><x-stars :rating="$r->rating"/><time class="text-xs text-muted" datetime="{{ $r->created_at->toDateString() }}">{{ $r->created_at->format('d M Y') }}</time></div>
                                @if($r->title)<h4 class="mt-2 text-base">{{ $r->title }}</h4>@endif
                                <p class="mt-1 text-sm leading-6 text-[#444]">{{ $r->body }}</p>
                                @if($r->image)<img src="{{ img($r->image) }}" alt="Customer photo" class="mt-3 h-24 w-24 rounded-xl object-cover" loading="lazy">@endif
                                <p class="mt-2 flex items-center gap-2 text-xs"><b>{{ $r->name }}</b>@if($r->is_verified)<span class="badge bg-green-50 text-green-700">✓ Verified purchase</span>@endif</p>
                            </article>
                        @empty<p class="text-muted">Be the first to review this product.</p>@endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if($upsells->isNotEmpty())
        <section class="mt-10"><h2 class="mb-5 text-xl sm:text-2xl">You may also like</h2><div class="grid grid-cols-2 gap-x-3 gap-y-6 sm:gap-x-5 lg:grid-cols-4">@foreach($upsells as $x)<x-product-card :product="$x"/>@endforeach</div></section>
    @endif
    @if($related->isNotEmpty())
        <section class="mt-12"><h2 class="mb-5 text-xl sm:text-2xl">Related Products</h2><div class="grid grid-cols-2 gap-x-3 gap-y-6 sm:gap-x-5 lg:grid-cols-4">@foreach($related->take(4) as $x)<x-product-card :product="$x"/>@endforeach</div></section>
    @endif
    @if($recent->isNotEmpty())
        <section class="mt-12"><h2 class="mb-5 text-xl sm:text-2xl">Recently Viewed</h2><div class="grid grid-cols-2 gap-x-3 gap-y-6 sm:gap-x-5 lg:grid-cols-4">@foreach($recent->take(4) as $x)<x-product-card :product="$x"/>@endforeach</div></section>
    @endif

    {{-- Sticky mobile add-to-cart: sits above the fixed bottom nav (4rem + safe area) --}}
    <div class="fixed inset-x-0 z-50 border-t border-line bg-white p-3 shadow-[0_-6px_20px_rgba(7,24,47,.08)] lg:hidden" style="bottom: calc(4rem + env(safe-area-inset-bottom))">
        <div class="flex items-center gap-3">
            <div class="min-w-0 flex-1"><p class="truncate text-xs text-muted">{{ $p->name }}</p><p class="text-lg font-bold" :class="onSale ? 'text-sale' : ''" x-text="money(price)"></p></div>
            <button class="btn btn-primary h-12 px-7" @click="add()" :disabled="adding || (complete && !canBuy)" x-text="complete && !canBuy ? 'Out of stock' : (adding ? 'Adding…' : 'Add to Cart')">Add to Cart</button>
        </div>
    </div>
    <div class="h-16 lg:hidden"></div>

    {{-- Lightbox --}}
    <div x-show="lightbox" x-cloak x-transition.opacity class="fixed inset-0 z-[110] flex items-center justify-center bg-black/90" @click.self="lightbox = false; $store.ui.lock(false)">
        <button class="absolute right-4 top-4 text-3xl text-white" @click="lightbox = false; $store.ui.lock(false)" aria-label="Close">✕</button>
        <button x-show="gallery.length > 1" class="absolute left-3 top-1/2 -translate-y-1/2 p-3 text-4xl text-white" @click="prev()" aria-label="Previous">‹</button>
        <img :src="gallery[main]?.url" :alt="gallery[main]?.alt" class="max-h-[90vh] max-w-[92vw] rounded-lg object-contain">
        <button x-show="gallery.length > 1" class="absolute right-3 top-1/2 -translate-y-1/2 p-3 text-4xl text-white" @click="next()" aria-label="Next">›</button>
        <p class="absolute bottom-4 text-sm text-white/70" x-text="(main + 1) + ' / ' + gallery.length"></p>
    </div>
</div>
@endsection
