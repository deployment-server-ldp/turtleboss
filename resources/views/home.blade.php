@extends('layouts.app')
@section('content')
<h1 class="sr-only">{{ setting('site_name', 'Turtle Boss') }} – {{ setting('tagline') }}</h1>

{{-- Hero slider --}}
@if($sliders->isNotEmpty())
<section class="relative" x-data="heroSlider({{ $sliders->count() }}, {{ (int) setting('slider_interval', 5000) }})" @mouseenter="stop()" @mouseleave="play()" @touchstart.passive="touchStart($event)" @touchend.passive="touchEnd($event)" aria-roledescription="carousel">
    <div class="relative aspect-[4/5] w-full overflow-hidden bg-soft sm:aspect-[16/7] lg:aspect-[1920/700]">
        @foreach($sliders as $n => $s)
            <div class="absolute inset-0 transition-opacity duration-700" :class="i === {{ $n }} ? 'opacity-100 z-10' : 'opacity-0 pointer-events-none'" @if($n) aria-hidden="true" @endif>
                <picture>
                    @if($s->image_mobile)<source media="(max-width: 639px)" srcset="{{ img($s->image_mobile) }}">@endif
                    <img src="{{ img($s->image_desktop ?: $s->image_mobile) }}" alt="{{ $s->alt ?: $s->heading }}" class="h-full w-full object-cover" @if($n) loading="lazy" @else fetchpriority="high" @endif width="1920" height="700">
                </picture>
                @if($s->heading || $s->subheading || $s->cta_text)
                <div class="absolute inset-0 flex items-end bg-gradient-to-t from-navy/60 via-transparent to-transparent sm:items-center sm:bg-gradient-to-r sm:from-navy/55 sm:via-navy/15">
                    <div class="container-x pb-10 sm:pb-0">
                        <div class="max-w-xl text-white">
                            @if($s->heading)<h2 class="text-3xl font-bold leading-tight !text-white sm:text-5xl">{{ $s->heading }}</h2>@endif
                            @if($s->subheading)<p class="mt-3 text-base text-white/90 sm:text-lg">{{ $s->subheading }}</p>@endif
                            @if($s->cta_text && $s->cta_url)<a href="{{ url($s->cta_url) }}" class="btn mt-6 bg-white !text-navy hover:bg-soft">{{ $s->cta_text }}</a>@endif
                        </div>
                    </div>
                </div>
                @endif
            </div>
        @endforeach
    </div>
    @if($sliders->count() > 1)
        <button class="absolute left-3 top-1/2 z-20 hidden h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-white/90 shadow sm:grid" @click="prev(); play()" aria-label="Previous slide">‹</button>
        <button class="absolute right-3 top-1/2 z-20 hidden h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-white/90 shadow sm:grid" @click="next(); play()" aria-label="Next slide">›</button>
        <div class="absolute inset-x-0 bottom-3 z-20 flex justify-center gap-2">
            @foreach($sliders as $n => $s)<button class="h-2 rounded-full bg-white/70 transition-all" :class="i === {{ $n }} ? 'w-7 !bg-white' : 'w-2'" @click="i = {{ $n }}; play()" aria-label="Go to slide {{ $n + 1 }}"></button>@endforeach
        </div>
    @endif
</section>
@endif

@forelse($sections as $x)
    @php $s = $x['section']; $d = $x['data']; $cfg = $s->config ?? []; @endphp
    @switch($s->type)
        @case('categories')
            <section class="container-x pt-12 sm:pt-16">
                @include('partials.section-head', ['s' => $s])
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4 xl:grid-cols-{{ min(7, max(4, $d['categories']->count())) }}">
                    @foreach($d['categories'] as $c)
                        <a href="{{ $c->url }}" class="group text-center">
                            <div class="aspect-square overflow-hidden rounded-[14px] bg-soft"><img src="{{ img($c->image) }}" alt="{{ $c->name }}" loading="lazy" width="300" height="300" class="h-full w-full object-cover transition duration-500 group-hover:scale-105"></div>
                            <p class="mt-2.5 text-sm font-semibold">{{ $c->name }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @break
        @case('products')
            <section class="container-x pt-12 sm:pt-16">
                @include('partials.section-head', ['s' => $s, 'link' => $cfg['cta_url'] ?? null, 'linkText' => $cfg['cta_text'] ?? 'View all'])
                <div class="grid grid-cols-2 gap-x-3 gap-y-6 sm:gap-x-5 lg:grid-cols-4">
                    @foreach($d['products'] as $p)<x-product-card :product="$p"/>@endforeach
                </div>
            </section>
        @break
        @case('banners')
            <section class="container-x grid gap-4 pt-12 sm:pt-16 {{ count($cfg['items']) > 1 ? 'md:grid-cols-2' : '' }}">
                @foreach($cfg['items'] as $b)
                    <a href="{{ url($b['cta_url'] ?: '/shop') }}" class="group relative block overflow-hidden rounded-[18px] bg-soft">
                        <img src="{{ img($b['image']) }}" alt="{{ $b['alt'] ?: $b['heading'] }}" loading="lazy" class="aspect-[16/9] w-full object-cover transition duration-500 group-hover:scale-105">
                        @if($b['heading'] || $b['text'])
                        <div class="absolute inset-0 flex flex-col justify-end bg-gradient-to-t from-navy/65 to-transparent p-5 text-white sm:p-7">
                            <h3 class="text-xl !text-white sm:text-2xl">{{ $b['heading'] }}</h3>
                            @if($b['text'])<p class="mt-1 text-sm text-white/90">{{ $b['text'] }}</p>@endif
                            @if($b['cta_text'])<span class="mt-3 inline-flex w-fit items-center rounded-xl bg-white px-4 py-2 text-sm font-semibold text-navy">{{ $b['cta_text'] }}</span>@endif
                        </div>@endif
                    </a>
                @endforeach
            </section>
        @break
        @case('why_choose')
            <section class="mt-12 bg-soft py-12 sm:mt-16 sm:py-16"><div class="container-x">
                @include('partials.section-head', ['s' => $s])
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    @foreach($cfg['items'] ?? [] as $it)
                        <div class="rounded-[14px] border border-line bg-white p-5 text-center sm:p-7">
                            <div class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-full bg-navy text-white">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    @switch($it['icon'] ?? 'badge')
                                        @case('smile')<circle cx="12" cy="12" r="9"/><path d="M8 14s1.500 2 4 2 4-2 4-2M9 9.500h.01M15 9.500h.01"/>@break
                                        @case('lock')<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 118 0v3"/>@break
                                        @case('truck')<path d="M3 6h11v9H3zM14 9h4l3 3v3h-7M7 18.500a1.500 1.500 0 100-3 1.500 1.500 0 000 3zM17 18.500a1.500 1.500 0 100-3 1.500 1.500 0 000 3z"/>@break
                                        @default<path d="M12 3l2.600 5.300 5.900.9-4.300 4.100 1 5.800L12 16.300 6.800 19.100l1-5.800L3.500 9.200l5.900-.9L12 3z"/>
                                    @endswitch
                                </svg>
                            </div>
                            <h3 class="text-base">{{ $it['title'] }}</h3><p class="mt-1 text-sm text-muted">{{ $it['text'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div></section>
        @break
        @case('testimonials')
            <section class="container-x pt-12 sm:pt-16">
                @include('partials.section-head', ['s' => $s])
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($d['reviews'] as $r)
                        <figure class="card p-6">
                            <x-stars :rating="$r->rating"/>
                            <blockquote class="mt-3 text-sm leading-6 text-[#444]">“{{ \Illuminate\Support\Str::limit($r->body, 220) }}”</blockquote>
                            <figcaption class="mt-4 flex items-center justify-between text-sm"><span class="font-semibold">{{ $r->name }}</span>@if($r->is_verified)<span class="badge bg-green-50 text-green-700">Verified purchase</span>@endif</figcaption>
                            @if($r->product)<a href="{{ $r->product->url }}" class="mt-2 block text-xs text-muted hover:underline">on {{ $r->product->name }}</a>@endif
                        </figure>
                    @endforeach
                </div>
            </section>
        @break
        @case('newsletter')
            <section class="container-x pt-12 sm:pt-16"><div class="rounded-[20px] bg-navy px-6 py-10 text-center text-white sm:py-14" x-data="{ email: '', done: false, err: '' , async go() { const r = await (await fetch('{{ route('newsletter') }}', {method:'POST', headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json','Content-Type':'application/json'}, body: JSON.stringify({email: this.email})})).json(); r.ok ? (this.done = true) : (this.err = r.errors?.email?.[0] || 'Please enter a valid email') } }">
                <h2 class="text-2xl !text-white sm:text-3xl">{{ $s->title }}</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-white/80">{{ $s->subtitle }}</p>
                <form x-show="!done" @submit.prevent="go()" class="mx-auto mt-6 flex max-w-md flex-col gap-2 sm:flex-row">
                    <input x-model="email" type="email" required placeholder="Your email address" class="field !border-transparent sm:flex-1" aria-label="Email">
                    <button class="btn bg-white !text-navy hover:bg-soft">Subscribe</button>
                </form>
                <p x-show="err" x-text="err" class="mt-2 text-sm text-red-300"></p>
                <p x-show="done" x-cloak class="mt-6 font-medium">✓ Thanks for subscribing!</p>
            </div></section>
        @break
        @case('rich_text')
            <section class="container-x pt-12 sm:pt-16"><div class="prose-tb">{!! $cfg['html'] ?? '' !!}</div></section>
        @break
    @endswitch
@empty
    <section class="container-x py-20 text-center"><h2 class="text-2xl">Welcome to Turtle Boss</h2><p class="mt-2 text-muted">Products will appear here once added from the admin dashboard.</p><a href="{{ route('shop') }}" class="btn btn-primary mt-6">Browse shop</a></section>
@endforelse
@endsection
