<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#07182F">
    @include('partials.seo', ['seo' => $seo ?? \App\Support\Seo::make()])
    @if($fav = setting('favicon'))<link rel="icon" href="{{ img($fav) }}">@endif
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>window.__TB = @json($boot);</script>
    @if($ga = setting('ga_id'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga }}"></script>
        <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','{{ $ga }}');</script>
    @endif
    @stack('head')
</head>
<body class="mobile-safe min-h-screen flex flex-col" x-data>
<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-2 focus:top-2 focus:z-[100] focus:rounded focus:bg-white focus:p-2">Skip to content</a>

@include('partials.announcement')
@include('partials.header')

<main id="main" class="flex-1">
    @if(session('status'))
        <div class="container-x pt-4"><div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">{{ session('status') }}</div></div>
    @endif
    @yield('content')
</main>

@include('partials.footer')
@include('partials.mobile-nav')
@include('partials.cart-drawer')

{{-- Quick view modal --}}
<div x-data="quickView" @quick-view.window="show($event.detail)" @keydown.escape.window="open && close()">
    <div x-show="open" x-cloak class="fixed inset-0 z-[80] flex items-end justify-center bg-navy/50 p-0 sm:items-center sm:p-6" @click.self="close()" x-transition.opacity>
        <div class="relative max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-t-3xl bg-white sm:rounded-3xl" x-ref="body">
            <button class="absolute right-3 top-3 z-10 grid h-9 w-9 place-items-center rounded-full bg-white shadow" @click="close()" aria-label="Close">✕</button>
            <div x-show="loading" class="grid h-64 place-items-center text-muted">Loading…</div>
            <div x-html="html"></div>
        </div>
    </div>
</div>

{{-- Toasts --}}
<div class="pointer-events-none fixed inset-x-0 top-4 z-[120] flex flex-col items-center gap-2 px-4" aria-live="polite">
    <template x-for="t in $store.ui.toasts" :key="t.id">
        <div class="pointer-events-auto rounded-xl px-4 py-3 text-sm font-medium text-white shadow-lg" :class="t.type === 'err' ? 'bg-red-600' : 'bg-navy'" x-text="t.message" x-transition></div>
    </template>
</div>
</body>
</html>
