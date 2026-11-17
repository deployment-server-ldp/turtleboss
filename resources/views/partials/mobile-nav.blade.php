@php
    $tabs = [
        ['Home', route('home'), request()->routeIs('home'), 'M3 11l9-7 9 7v9a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1v-9z'],
        ['Shop', route('shop'), request()->routeIs('shop', 'product.show'), 'M4 7h16l-1.500 12a1 1 0 01-1 .9H6.500a1 1 0 01-1-.9L4 7zm4 0a4 4 0 018 0'],
    ];
@endphp
{{-- Categories bottom sheet (mobile) --}}
<div x-data x-show="$store.ui.catSheet" x-cloak class="fixed inset-0 z-[70] lg:hidden" @keydown.escape.window="$store.ui.catSheet = false">
    <div class="absolute inset-0 bg-navy/50" @click="$store.ui.catSheet = false" x-transition.opacity></div>
    <div class="absolute inset-x-0 bottom-0 max-h-[82vh] overflow-y-auto rounded-t-3xl bg-white p-5 pb-24 shadow-2xl" x-show="$store.ui.catSheet" x-transition:enter="transition duration-300" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0" x-transition:leave="transition duration-200" x-transition:leave-end="translate-y-full">
        <div class="mx-auto mb-4 h-1 w-10 rounded-full bg-line"></div>
        <h2 class="mb-3 text-lg">Shop by Category</h2>
        <div class="space-y-1.5">
            @foreach($navCategories as $c)
                <div x-data="{ o: false }" class="rounded-2xl border border-line">
                    <div class="flex items-center">
                        <a href="{{ $c->url }}" class="flex flex-1 items-center gap-3 p-3"><img src="{{ img($c->image) }}" alt="" class="h-11 w-11 rounded-xl bg-soft object-cover" loading="lazy"><span class="font-medium">{{ $c->name }}</span></a>
                        @if($c->children->isNotEmpty())<button class="p-3" @click="o = !o" aria-label="Show subcategories"><svg class="h-4 w-4 transition" :class="o && 'rotate-180'" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="M5.500 7.500L10 12l4.500-4.500"/></svg></button>@endif
                    </div>
                    @if($c->children->isNotEmpty())<ul x-show="o" x-cloak class="border-t border-line px-3 py-2 text-sm">@foreach($c->children as $ch)<li><a class="block py-2 text-muted" href="{{ $ch->url }}">{{ $ch->name }}</a></li>@endforeach</ul>@endif
                </div>
            @endforeach
        </div>
        <a href="{{ route('shop') }}" class="btn btn-primary mt-4 w-full">View all products</a>
    </div>
</div>

<nav class="fixed inset-x-0 bottom-0 z-[60] border-t border-line bg-white/95 shadow-[0_-4px_20px_rgba(7,24,47,.06)] backdrop-blur lg:hidden" style="padding-bottom: env(safe-area-inset-bottom)" aria-label="Mobile navigation" x-data>
    <ul class="mx-auto grid h-16 max-w-xl grid-cols-5">
        @foreach($tabs as [$label, $url, $active, $d])
            <li><a href="{{ $url }}" class="relative flex h-full flex-col items-center justify-center gap-0.5 text-[11px] font-medium {{ $active ? 'text-navy' : 'text-gray-400' }}" @if($active) aria-current="page" @endif>
                @if($active)<span class="absolute top-0 h-[3px] w-8 rounded-b-full bg-navy"></span>@endif
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $d }}"/></svg>{{ $label }}</a></li>
        @endforeach
        <li><button type="button" @click="$store.ui.catSheet = !$store.ui.catSheet" class="relative flex h-full w-full flex-col items-center justify-center gap-0.5 text-[11px] font-medium" :class="$store.ui.catSheet ? 'text-navy' : 'text-gray-400'">
            <span x-show="$store.ui.catSheet" class="absolute top-0 h-[3px] w-8 rounded-b-full bg-navy"></span>
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><rect x="4" y="4" width="6.500" height="6.500" rx="1.500"/><rect x="13.500" y="4" width="6.500" height="6.500" rx="1.500"/><rect x="4" y="13.500" width="6.500" height="6.500" rx="1.500"/><rect x="13.500" y="13.500" width="6.500" height="6.500" rx="1.500"/></svg>Categories</button></li>
        <li><a href="{{ route('wishlist') }}" class="relative flex h-full flex-col items-center justify-center gap-0.5 text-[11px] font-medium {{ request()->routeIs('wishlist') ? 'text-navy' : 'text-gray-400' }}">
            @if(request()->routeIs('wishlist'))<span class="absolute top-0 h-[3px] w-8 rounded-b-full bg-navy"></span>@endif
            <span class="relative"><svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7.500-4.600-9.500-9.200C1.200 8.700 3 5.500 6.200 5.500c2 0 3.200 1 3.800 2 .6-1 1.800-2 3.800-2 3.200 0 5 3.200 3.700 6.300C19.500 16.400 12 21 12 21z"/></svg>
            <span x-show="$store.wish.count" x-cloak x-text="$store.wish.count" class="absolute -right-2 -top-1.5 grid h-4 min-w-4 place-items-center rounded-full bg-navy px-1 text-[9px] font-bold text-white"></span></span>Wishlist</a></li>
        <li><a href="{{ auth()->check() ? route('account') : route('login') }}" class="relative flex h-full flex-col items-center justify-center gap-0.5 text-[11px] font-medium {{ request()->is('account*', 'login', 'register') ? 'text-navy' : 'text-gray-400' }}">
            @if(request()->is('account*', 'login', 'register'))<span class="absolute top-0 h-[3px] w-8 rounded-b-full bg-navy"></span>@endif
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c1-4 4-6 8-6s7 2 8 6"/></svg>Account</a></li>
    </ul>
</nav>
