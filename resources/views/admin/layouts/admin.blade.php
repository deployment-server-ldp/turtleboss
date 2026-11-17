<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"><meta name="robots" content="noindex,nofollow">
    <title>@yield('title', 'Dashboard') · Turtle Boss Admin</title>
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap">
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
</head>
@php
    $u = auth()->user();
    $can = fn ($p) => $u->hasPermission($p);
    $nav = [
        ['Dashboard', 'admin.dashboard', 'dashboard', 'M3 12l9-8 9 8M5 10v10h14V10', null],
        ['Catalog', null, 'products', 'M4 7h16l-1.500 12H5.500L4 7zm4 0a4 4 0 018 0', [
            ['Products', 'admin.products.index', 'products', 'admin/products*'], ['Categories', 'admin.categories.index', 'categories', 'admin/categories*'],
            ['Product Attributes', 'admin.attributes.index', 'products', 'admin/attributes*'], ['Inventory', 'admin.inventory.index', 'inventory', 'admin/inventory*'],
        ]],
        ['Sales', null, 'orders', 'M3 4h2l2.400 11.200a1 1 0 001 .8h8.700a1 1 0 001-.8L20 8H6.200', [
            ['Orders', 'admin.orders.index', 'orders', 'admin/orders*'], ['Customers', 'admin.customers.index', 'customers', 'admin/customers*'],
            ['Coupons & Discounts', 'admin.coupons.index', 'coupons', 'admin/coupons*'], ['Reviews', 'admin.reviews.index', 'reviews', 'admin/reviews*'], ['Wishlist Analytics', 'admin.wishlist', 'reviews', 'admin/wishlist-analytics'],
            ['Inbox & Subscribers', 'admin.inbox', 'customers', 'admin/inbox'],
        ]],
        ['Content', null, 'content', 'M4 5h16M4 12h16M4 19h10', [
            ['Homepage Builder', 'admin.homepage.index', 'content', 'admin/homepage*'], ['Slider Management', 'admin.sliders.index', 'content', 'admin/sliders*'],
            ['Menu Management', 'admin.menus.index', 'content', 'admin/menus*'], ['Pages', 'admin.pages.index', 'content', 'admin/pages*'],
            ['Blog Posts', 'admin.blog.index', 'content', 'admin/blog', 'admin/blog/*'], ['Blog Categories', 'admin.blog-categories.index', 'content', 'admin/blog-categories*'], ['Blog Tags', 'admin.blog-tags.index', 'content', 'admin/blog-tags*'],
            ['Media Library', 'admin.media.index', 'content', 'admin/media*'],
        ]],
        ['SEO', null, 'seo', 'M11 4a7 7 0 100 14 7 7 0 000-14zm9 16l-4-4', [['SEO Management', 'admin.seo', 'seo', 'admin/seo*'], ['Global SEO', 'admin.settings.show', 'seo', 'admin/settings/seo', ['seo']], ['Redirects', 'admin.redirects.index', 'seo', 'admin/redirects*']]],
        ['Settings', null, 'settings', 'M12 15a3 3 0 100-6 3 3 0 000 6zm7-3l2 1-2 3-2-.5-1.500 1 .5 2.500h-3.500L12 20l-1.500-1 .5-2.500-1.500-1-2 .5-2-3 2-1', [
            ['General', 'admin.settings.show', 'settings', 'admin/settings/general', ['general']], ['Shipping Settings', 'admin.settings.show', 'settings', 'admin/settings/shipping', ['shipping']], ['Shipping Rules', 'admin.shipping-rules.index', 'settings', 'admin/shipping-rules*'],
            ['Payment Settings', 'admin.settings.show', 'settings', 'admin/settings/payment', ['payment']], ['Email Settings', 'admin.settings.show', 'settings', 'admin/settings/email', ['email']], ['WhatsApp Settings', 'admin.settings.show', 'settings', 'admin/settings/whatsapp', ['whatsapp']],
        ]],
        ['Users & Roles', 'admin.users.index', 'users', 'M16 11a4 4 0 10-8 0 4 4 0 008 0zM4 21c1-4 4-6 8-6s7 2 8 6', null],
        ['Reports', 'admin.reports', 'reports', 'M5 20V10m7 10V4m7 16v-7', null],
        ['Backup & Maintenance', 'admin.backup.index', 'backup', 'M4 7c0-1.700 3.600-3 8-3s8 1.300 8 3-3.600 3-8 3-8-1.300-8-3zm0 0v10c0 1.700 3.600 3 8 3s8-1.300 8-3V7', null],
    ];
@endphp
<body class="bg-soft text-[14px]" x-data="{ side: false }">
<div class="flex min-h-screen">
    <div class="fixed inset-0 z-30 bg-navy/50 lg:hidden" x-show="side" x-cloak @click="side = false"></div>
    <aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-navy text-white transition lg:translate-x-0" :class="side && '!translate-x-0'">
        <div class="flex h-16 items-center gap-2 border-b border-white/10 px-5"><span class="text-lg font-extrabold tracking-tight">TURTLE <span class="text-sky-300">BOSS</span></span><span class="ml-auto rounded bg-white/10 px-1.5 py-0.5 text-[10px]">ADMIN</span></div>
        <nav class="flex-1 space-y-0.5 overflow-y-auto p-3" aria-label="Admin">
            @foreach($nav as [$label, $route, $perm, $icon, $children])
                @continue(!$can($perm))
                @if($children)
                    @php $open = collect($children)->contains(fn ($c) => request()->is($c[3]) || (isset($c[4]) && is_string($c[4]) && request()->is($c[4]))); @endphp
                    <div x-data="{ o: {{ $open ? 'true' : 'false' }} }">
                        <button @click="o = !o" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-white/80 hover:bg-white/10 hover:text-white"><svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg><span class="flex-1">{{ $label }}</span><svg class="h-3.5 w-3.5 transition" :class="o && 'rotate-180'" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="M5.500 7.500L10 12l4.500-4.500"/></svg></button>
                        <ul x-show="o" x-cloak class="ml-5 mt-0.5 space-y-0.5 border-l border-white/10 pl-3">
                            @foreach($children as $c)
                                @continue(!$can($c[2]))
                                @php $href = isset($c[4]) && is_array($c[4]) ? route($c[1], $c[4]) : route($c[1]); $on = request()->is($c[3]); @endphp
                                <li><a href="{{ $href }}" class="block rounded-md px-3 py-1.5 {{ $on ? 'bg-white/15 font-semibold text-white' : 'text-white/65 hover:text-white' }}">{{ $c[0] }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 {{ request()->routeIs($route . '*') ? 'bg-white/15 font-semibold' : 'text-white/80 hover:bg-white/10 hover:text-white' }}"><svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg>{{ $label }}</a>
                @endif
            @endforeach
        </nav>
        <div class="border-t border-white/10 p-3 text-xs"><a href="{{ route('home') }}" target="_blank" class="block rounded-lg px-3 py-2 text-white/70 hover:bg-white/10">↗ View storefront</a></div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col lg:ml-64">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-line bg-white px-4 sm:px-6">
            <button class="grid h-10 w-10 place-items-center rounded-lg hover:bg-soft lg:hidden" @click="side = true" aria-label="Menu"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
            <h1 class="truncate text-lg font-semibold">@yield('title', 'Dashboard')</h1>
            <div class="ml-auto flex items-center gap-3">
                <div class="hidden text-right leading-tight sm:block"><p class="text-sm font-semibold">{{ $u->name }}</p><p class="text-xs text-muted">{{ $u->role?->name }}</p></div>
                <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-outline btn-sm">Log out</button></form>
            </div>
        </header>
        <main class="flex-1 p-4 sm:p-6">
            @if(session('status'))<div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-800" role="status">{{ session('status') }}</div>@endif
            @if($errors->any() && !isset($hideErrors))<div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-700"><ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
