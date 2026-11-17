@props(['product'])
@php $p = $product; $img = $p->images->first(); @endphp
<article class="group relative flex flex-col">
    <div class="relative aspect-square overflow-hidden rounded-[14px] bg-soft">
        <a href="{{ $p->url }}" class="block h-full w-full" aria-label="{{ $p->name }}">
            <img src="{{ $p->image_url }}" alt="{{ $p->image_alt }}" loading="lazy" decoding="async" width="400" height="400"
                 class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
        </a>
        @if($p->on_sale)<span class="badge absolute left-2.5 top-2.5 bg-sale text-white">-{{ $p->discount_percent }}%</span>
        @elseif(!$p->in_stock)<span class="badge absolute left-2.5 top-2.5 bg-gray-800 text-white">Sold out</span>@endif
        <button type="button" @click.prevent="$store.wish.toggle({{ $p->id }})" aria-label="Wishlist"
                class="absolute right-2.5 top-2.5 grid h-9 w-9 place-items-center rounded-full bg-white/95 shadow-sm transition hover:scale-105">
            <svg class="h-[18px] w-[18px]" :class="$store.wish.has({{ $p->id }}) ? 'fill-red-500 text-red-500' : 'fill-none text-navy'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7.5-4.6-9.5-9.2C1.2 8.7 3 5.5 6.2 5.5c2 0 3.2 1 3.8 2 .6-1 1.8-2 3.8-2 3.200 0 5 3.200 3.700 6.300C19.500 16.400 12 21 12 21z"/></svg>
        </button>
        <div class="absolute inset-x-2.5 bottom-2.5 hidden gap-2 opacity-0 transition group-hover:opacity-100 lg:flex">
            <button type="button" class="btn btn-outline btn-sm flex-1 bg-white/95" @click.prevent="$dispatch('quick-view', '{{ $p->slug }}')">Quick View</button>
            @if($p->in_stock && !$p->has_variants)
                <button type="button" class="btn btn-primary btn-sm flex-1" @click.prevent="$store.cart.add({{ $p->id }})">Add to Cart</button>
            @elseif($p->in_stock)
                <a href="{{ $p->url }}" class="btn btn-primary btn-sm flex-1">Options</a>
            @endif
        </div>
    </div>
    <div class="mt-3 flex flex-1 flex-col">
        <a href="{{ $p->url }}" class="line-clamp-2 min-h-[2.5rem] text-sm font-medium text-navy hover:underline">{{ $p->name }}</a>
        @if($p->rating_count > 0)<div class="mt-1"><x-stars :rating="$p->rating_avg" :count="$p->rating_count"/></div>@endif
        <div class="mt-1.5 flex flex-wrap items-baseline gap-x-2">
            @if($p->has_variants && $p->variants->where('is_active', true)->count() > 1)<span class="text-xs text-muted">From</span>@endif
            <span class="text-base font-bold {{ $p->on_sale ? 'text-sale' : 'text-navy' }}">{{ money($p->final_price) }}</span>
            @if($p->on_sale)<span class="text-xs text-muted line-through">{{ money($p->regular_price) }}</span>@endif
        </div>
        {{-- mobile quick add --}}
        @if($p->in_stock)
            <button type="button" class="btn btn-outline btn-sm mt-2.5 lg:hidden"
                @if($p->has_variants) @click="location.href='{{ $p->url }}'" @else @click.prevent="$store.cart.add({{ $p->id }})" @endif>
                {{ $p->has_variants ? 'Select Options' : 'Add to Cart' }}
            </button>
        @else
            <span class="mt-2.5 text-xs font-medium text-muted lg:hidden">Out of stock</span>
        @endif
    </div>
</article>
