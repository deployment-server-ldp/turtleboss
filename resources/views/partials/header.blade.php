<header class="z-40 border-b border-line bg-white lg:sticky lg:top-0" x-data="{ mega: false }" @keydown.escape.window="mega = false; $store.ui.search = false">
    <div class="container-x flex h-16 items-center gap-4 lg:h-[72px]">
        <a href="{{ route('home') }}" class="shrink-0" aria-label="{{ setting('site_name', 'Turtle Boss') }} home"><x-logo class="h-8 w-auto lg:h-10"/></a>

        {{-- Desktop nav (from admin-managed header menu) --}}
        <nav class="mx-auto hidden items-center gap-1 lg:flex" aria-label="Main">
            @foreach($headerMenu as $item)
                @if($item->type === 'mega_categories')
                    <div class="relative" @mouseenter="mega = true" @mouseleave="mega = false">
                        <button class="flex items-center gap-1 rounded-lg px-3.5 py-2 text-sm font-medium hover:bg-soft" :aria-expanded="mega" @click="mega = !mega">
                            {{ $item->label }}
                            <svg class="h-3.5 w-3.5 transition" :class="mega && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor"><path d="M5.5 7.5L10 12l4.5-4.5"/></svg>
                        </button>
                        <div x-show="mega" x-cloak x-transition.opacity.duration.150ms class="absolute left-1/2 top-full z-50 w-[760px] -translate-x-1/2 pt-3">
                            <div class="card grid grid-cols-3 gap-x-6 gap-y-5 p-6 shadow-xl">
                                @foreach($navCategories as $c)
                                    <div>
                                        <a href="{{ $c->url }}" class="flex items-center gap-3 font-semibold text-navy hover:text-brand">
                                            <img src="{{ img($c->image) }}" alt="" class="h-10 w-10 rounded-lg bg-soft object-cover" loading="lazy" width="40" height="40">{{ $c->name }}
                                        </a>
                                        @if($c->children->isNotEmpty())
                                            <ul class="mt-2 space-y-1 pl-[52px] text-sm text-muted">
                                                @foreach($c->children as $ch)<li><a href="{{ $ch->url }}" class="hover:text-navy">{{ $ch->name }}</a></li>@endforeach
                                            </ul>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @elseif($item->children->isNotEmpty())
                    <div class="relative" x-data="{ o: false }" @mouseenter="o = true" @mouseleave="o = false">
                        <a href="{{ $item->href }}" class="flex items-center gap-1 rounded-lg px-3.5 py-2 text-sm font-medium hover:bg-soft">{{ $item->label }}<svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path d="M5.5 7.5L10 12l4.5-4.5"/></svg></a>
                        <div x-show="o" x-cloak class="absolute left-0 top-full z-50 pt-2"><ul class="card min-w-[200px] p-2 shadow-xl">
                            @foreach($item->children as $ch)<li><a href="{{ $ch->href }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-soft" @if($ch->open_new_tab) target="_blank" rel="noopener" @endif>{{ $ch->label }}</a></li>@endforeach
                        </ul></div>
                    </div>
                @else
                    <a href="{{ $item->href }}" @if($item->open_new_tab) target="_blank" rel="noopener" @endif
                       class="rounded-lg px-3.5 py-2 text-sm font-medium hover:bg-soft {{ request()->fullUrlIs($item->href) || ($item->url !== '/' && request()->is(ltrim($item->url, '/'))) ? 'text-brand' : '' }}">{{ $item->label }}</a>
                @endif
            @endforeach
        </nav>

        <div class="ml-auto flex items-center gap-1 lg:ml-0">
            <button class="grid h-10 w-10 place-items-center rounded-full hover:bg-soft" @click="$store.ui.search = true; $nextTick(() => $refs.sq?.focus())" aria-label="Search">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M20 20l-3.5-3.5"/></svg>
            </button>
            <a href="{{ auth()->check() ? route('account') : route('login') }}" class="hidden h-10 w-10 place-items-center rounded-full hover:bg-soft lg:grid" aria-label="My account">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path stroke-linecap="round" d="M4 21c1-4 4-6 8-6s7 2 8 6"/></svg>
            </a>
            <a href="{{ route('wishlist') }}" class="relative hidden h-10 w-10 place-items-center rounded-full hover:bg-soft lg:grid" aria-label="Wishlist">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7.5-4.6-9.5-9.2C1.2 8.7 3 5.5 6.2 5.5c2 0 3.200 1 3.800 2 .6-1 1.800-2 3.800-2 3.200 0 5 3.200 3.700 6.300C19.500 16.400 12 21 12 21z"/></svg>
                <span x-show="$store.wish.count" x-text="$store.wish.count" x-cloak class="absolute -right-0.5 -top-0.5 grid h-[18px] min-w-[18px] place-items-center rounded-full bg-navy px-1 text-[10px] font-bold text-white"></span>
            </a>
            <button class="relative grid h-10 w-10 place-items-center rounded-full hover:bg-soft" @click="$store.ui.drawer = true; $store.ui.lock(true); $store.cart.refresh()" aria-label="Open cart">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h2l2.4 11.200a1 1 0 001 .8h8.700a1 1 0 001-.8L20 8H6.200"/><circle cx="9.500" cy="20" r="1.200"/><circle cx="17" cy="20" r="1.200"/></svg>
                <span x-text="$store.cart.count" x-show="$store.cart.count > 0" class="absolute -right-0.5 -top-0.5 grid h-[18px] min-w-[18px] place-items-center rounded-full bg-navy px-1 text-[10px] font-bold text-white">{{ $boot['cart']['count'] }}</span>
            </button>
        </div>
    </div>

    {{-- Search overlay --}}
    <div x-show="$store.ui.search" x-cloak x-transition.opacity class="fixed inset-0 z-[90] bg-navy/40" @click.self="$store.ui.search = false">
        <div class="mx-auto mt-0 w-full max-w-2xl bg-white p-4 shadow-2xl sm:mt-16 sm:rounded-2xl" x-data="liveSearch">
            <form @submit.prevent="go()" class="flex items-center gap-3 rounded-xl border border-line px-3.5 focus-within:border-navy">
                <svg class="h-5 w-5 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M20 20l-3.5-3.5"/></svg>
                <input x-ref="sq" x-model="q" @input="input()" type="search" placeholder="Search products, SKU, brand or category" class="w-full border-0 bg-transparent py-3.5 text-base outline-none" autocomplete="off" aria-label="Search">
                <button type="button" class="text-sm text-muted" @click="$store.ui.search = false">Close</button>
            </form>
            <div class="mt-2 max-h-[60vh] overflow-y-auto">
                <p x-show="loading" class="p-4 text-sm text-muted">Searching…</p>
                <template x-for="r in results" :key="r.url">
                    <a :href="r.url" class="flex items-center gap-3 rounded-xl p-2.5 hover:bg-soft">
                        <img :src="r.image" alt="" class="h-14 w-14 rounded-lg bg-soft object-cover" loading="lazy">
                        <span class="min-w-0 flex-1"><span class="block truncate text-sm font-medium" x-text="r.name"></span>
                            <span class="text-sm font-bold" x-text="r.price"></span> <span class="text-xs text-muted line-through" x-text="r.regular"></span></span>
                    </a>
                </template>
                <p x-show="done && !results.length && !loading" class="p-4 text-sm text-muted">No products found. Try a different word.</p>
                <button x-show="results.length" @click="go()" class="mt-1 w-full rounded-xl p-3 text-sm font-semibold text-brand hover:bg-soft">See all results →</button>
            </div>
        </div>
    </div>
</header>
