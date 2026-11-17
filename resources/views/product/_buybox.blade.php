{{-- Shared by product page and quick view. Requires Alpine scope `productPage`. --}}
@if($config['hasVariants'])
    @foreach($config['attributes'] as $a)
    <div class="mt-5">
        <p class="mb-2 text-sm font-semibold">{{ $a['name'] }}: <span class="font-normal text-muted" x-text="(cfg.attributes.find(x => x.id === {{ $a['id'] }}).values.find(v => v.id === sel[{{ $a['id'] }}]) || {}).value || 'Select'"></span></p>
        <div class="flex flex-wrap gap-2">
            @foreach($a['values'] as $v)
                <button type="button" @click="pick({{ $a['id'] }}, {{ $v['id'] }})" :disabled="!optionAvailable({{ $a['id'] }}, {{ $v['id'] }})"
                        :aria-pressed="sel[{{ $a['id'] }}] === {{ $v['id'] }}" title="{{ $v['value'] }}"
                        class="relative transition disabled:cursor-not-allowed disabled:opacity-40
                        @if($a['type'] === 'color' && $v['hex']) h-10 w-10 rounded-full border border-line @else min-w-[3rem] rounded-xl border px-4 py-2.5 text-sm font-medium @endif"
                        :class="sel[{{ $a['id'] }}] === {{ $v['id'] }} ? 'ring-2 ring-navy ring-offset-2 {{ $a['type'] === 'color' && $v['hex'] ? '' : 'border-navy bg-navy text-white' }}' : '{{ $a['type'] === 'color' && $v['hex'] ? '' : 'border-line hover:border-navy' }}'"
                        @if($a['type'] === 'color' && $v['hex']) style="background: {{ $v['hex'] }}" @endif>
                    @if(!($a['type'] === 'color' && $v['hex'])){{ $v['value'] }}@else<span class="sr-only">{{ $v['value'] }}</span>@endif
                    <span x-show="!optionAvailable({{ $a['id'] }}, {{ $v['id'] }})" class="pointer-events-none absolute inset-0 overflow-hidden rounded-[inherit]"><span class="absolute left-0 top-1/2 h-px w-full -rotate-45 bg-gray-400"></span></span>
                </button>
            @endforeach
        </div>
    </div>
    @endforeach
@endif

<div class="mt-6 flex flex-wrap items-center gap-3">
    <div class="inline-flex items-center rounded-xl border border-line">
        <button type="button" class="h-12 w-11 text-lg disabled:opacity-30" @click="dec()" :disabled="qty <= 1" aria-label="Decrease quantity">−</button>
        <input type="number" x-model.number="qty" @change="clamp()" min="1" :max="maxQty" class="h-12 w-14 border-0 text-center text-base font-semibold outline-none [appearance:textfield]" aria-label="Quantity">
        <button type="button" class="h-12 w-11 text-lg disabled:opacity-30" @click="inc()" :disabled="qty >= maxQty" aria-label="Increase quantity">+</button>
    </div>
    <button type="button" class="btn btn-primary h-12 flex-1 min-w-[10rem]" @click="add()" :disabled="adding || (complete && !canBuy)">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h2l2.400 11.200a1 1 0 001 .8h8.700a1 1 0 001-.8L20 8H6.200"/></svg>
        <span x-text="adding ? 'Adding…' : (complete && !canBuy ? 'Out of stock' : 'Add to Cart')"></span>
    </button>
</div>
<div class="mt-3 flex gap-3">
    <button type="button" class="btn btn-outline h-12 flex-1" @click="buyNow()" :disabled="adding || (complete && !canBuy)">Buy Now</button>
    <button type="button" class="btn btn-outline h-12 w-12 !px-0" @click="$store.wish.toggle({{ $p->id }})" aria-label="Wishlist">
        <svg class="h-5 w-5" :class="$store.wish.has({{ $p->id }}) ? 'fill-red-500 text-red-500' : 'fill-none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7.500-4.600-9.500-9.200C1.200 8.700 3 5.500 6.200 5.500c2 0 3.200 1 3.800 2 .6-1 1.800-2 3.800-2 3.200 0 5 3.200 3.700 6.300C19.500 16.400 12 21 12 21z"/></svg>
    </button>
</div>
