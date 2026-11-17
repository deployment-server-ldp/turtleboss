@extends('layouts.app')
@php
    $q = request()->query();
    $url = fn (array $over = []) => url()->current() . '?' . http_build_query(array_filter(array_merge($q, $over), fn ($v) => $v !== null && $v !== '' && $v !== []));
    $activeAttr = (array) request('attr', []);
    $activeFilters = request()->hasAny(['min', 'max', 'availability', 'rating', 'brand', 'attr']);
@endphp
@section('content')
<div class="container-x py-6 sm:py-8" x-data="{ filters: false }">
    @include('partials.breadcrumbs')
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl">{{ $category?->name ?? (request('q') ? 'Results for “' . request('q') . '”' : 'Shop') }}</h1>
        @if($category?->description)<div class="prose-tb mt-2 max-w-3xl text-sm">{!! $category->description !!}</div>@endif
    </div>

    {{-- Subcategory chips --}}
    @if($category && $category->children->where('is_active', true)->isNotEmpty())
        <div class="no-scrollbar -mx-4 mb-5 flex gap-2 overflow-x-auto px-4">
            @foreach($category->children->where('is_active', true) as $ch)<a href="{{ $ch->url }}" class="shrink-0 rounded-full border border-line px-4 py-2 text-sm hover:border-navy">{{ $ch->name }}</a>@endforeach
        </div>
    @endif

    <div class="flex items-center justify-between gap-3 border-y border-line py-3">
        <button class="btn btn-outline btn-sm lg:hidden" @click="filters = true"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 6h16M7 12h10M10 18h4"/></svg>Filters @if($activeFilters)<span class="h-2 w-2 rounded-full bg-sale"></span>@endif</button>
        <p class="hidden text-sm text-muted lg:block">{{ $products->total() }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }}</p>
        <form method="get" class="ml-auto flex items-center gap-2 text-sm">
            @foreach($q as $k => $v) @if($k !== 'sort' && $k !== 'page' && !is_array($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif @endforeach
            <label for="sort" class="hidden text-muted sm:block">Sort by</label>
            <select id="sort" name="sort" class="rounded-xl border border-line bg-white py-2 pl-3 pr-8 text-sm" onchange="this.form.submit()">
                @foreach(['featured' => 'Featured', 'best' => 'Best Selling', 'newest' => 'Newest', 'price_asc' => 'Price: Low to High', 'price_desc' => 'Price: High to Low', 'rating' => 'Highest Rated'] as $k => $l)<option value="{{ $k }}" @selected(request('sort', 'featured') === $k)>{{ $l }}</option>@endforeach
            </select>
        </form>
    </div>

    <div class="mt-6 grid gap-8 lg:grid-cols-[250px_1fr]">
        {{-- Filters --}}
        <div class="fixed inset-0 z-[75] lg:!block lg:static lg:z-auto" x-show="filters" x-cloak>
            <div class="absolute inset-0 bg-navy/50 lg:hidden" @click="filters = false"></div>
            <aside class="absolute inset-y-0 left-0 w-[88%] max-w-sm overflow-y-auto bg-white p-5 pb-28 lg:static lg:w-auto lg:max-w-none lg:overflow-visible lg:p-0">
                <div class="mb-4 flex items-center justify-between lg:hidden"><h2 class="text-lg">Filters</h2><button @click="filters = false" aria-label="Close">✕</button></div>
                <form method="get" action="{{ url()->current() }}" class="space-y-6 text-sm">
                    @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
                    @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
                    <div>
                        <h3 class="mb-2 text-sm">Categories</h3>
                        <ul class="space-y-1.5">
                            <li><a href="{{ route('shop', array_filter(['q' => request('q')])) }}" class="{{ !$category ? 'font-semibold text-navy' : 'text-muted' }}">All products</a></li>
                            @foreach($categories as $c)
                                <li><a href="{{ $c->url }}" class="{{ $category?->id === $c->id ? 'font-semibold text-navy' : 'text-muted hover:text-navy' }}">{{ $c->name }}</a>
                                    @if($c->children->isNotEmpty() && ($category?->id === $c->id || $category?->parent_id === $c->id))<ul class="mt-1 space-y-1 border-l border-line pl-3">@foreach($c->children as $ch)<li><a href="{{ $ch->url }}" class="{{ $category?->id === $ch->id ? 'font-semibold text-navy' : 'text-muted' }}">{{ $ch->name }}</a></li>@endforeach</ul>@endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div>
                        <h3 class="mb-2 text-sm">Price (Rs.)</h3>
                        <div class="flex items-center gap-2"><input type="number" name="min" min="0" value="{{ request('min') }}" placeholder="Min" class="field !py-2"><span class="text-muted">–</span><input type="number" name="max" min="0" value="{{ request('max') }}" placeholder="{{ $priceMax ?: 'Max' }}" class="field !py-2"></div>
                    </div>
                    <div>
                        <h3 class="mb-2 text-sm">Availability</h3>
                        <label class="flex items-center gap-2"><input type="checkbox" name="availability" value="in_stock" @checked(request('availability') === 'in_stock') class="rounded border-line text-navy"> In stock only</label>
                    </div>
                    <div>
                        <h3 class="mb-2 text-sm">Rating</h3>
                        <select name="rating" class="field !py-2"><option value="">Any</option>@foreach([4, 3, 2] as $rt)<option value="{{ $rt }}" @selected(request('rating') == $rt)>{{ $rt }}★ & up</option>@endforeach</select>
                    </div>
                    @foreach($attributes as $a)
                        <div>
                            <h3 class="mb-2 text-sm">{{ $a->name }}</h3>
                            <div class="flex flex-wrap gap-2">
                                @foreach($a->values as $v)
                                    @php $on = in_array($v->id, (array) ($activeAttr[$a->id] ?? [])); @endphp
                                    <label class="cursor-pointer">
                                        <input type="checkbox" class="peer sr-only" name="attr[{{ $a->id }}][]" value="{{ $v->id }}" @checked($on)>
                                        @if($a->type === 'color' && $v->color_hex)
                                            <span class="block h-8 w-8 rounded-full border border-line ring-offset-2 peer-checked:ring-2 peer-checked:ring-navy" style="background: {{ $v->color_hex }}" title="{{ $v->value }}"></span>
                                        @else
                                            <span class="block rounded-lg border border-line px-3 py-1.5 peer-checked:border-navy peer-checked:bg-navy peer-checked:text-white">{{ $v->value }}</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    @if($brands->isNotEmpty())
                        <div>
                            <h3 class="mb-2 text-sm">Brand</h3>
                            <div class="space-y-1.5">@foreach($brands as $b)<label class="flex items-center gap-2"><input type="checkbox" name="brand[]" value="{{ $b }}" @checked(in_array($b, (array) request('brand', []))) class="rounded border-line text-navy"> {{ $b }}</label>@endforeach</div>
                        </div>
                    @endif
                    <div class="sticky bottom-0 -mx-5 flex gap-2 border-t border-line bg-white p-4 lg:static lg:mx-0 lg:border-0 lg:p-0">
                        <button class="btn btn-primary flex-1">Apply filters</button>
                        @if($activeFilters)<a href="{{ url()->current() }}" class="btn btn-outline">Clear</a>@endif
                    </div>
                </form>
            </aside>
        </div>

        <div x-data="loadMore('{{ $products->nextPageUrl() }}')">
            @if($products->isEmpty())
                <div class="card grid place-items-center p-12 text-center"><p class="text-lg font-semibold">No products found</p><p class="mt-1 text-sm text-muted">Try removing some filters or searching for something else.</p><a href="{{ route('shop') }}" class="btn btn-primary mt-5">Browse all</a></div>
            @else
                <div id="grid" class="grid grid-cols-2 gap-x-3 gap-y-7 sm:gap-x-5 lg:grid-cols-3 xl:grid-cols-4">@include('shop._grid_items')</div>
                {{-- SEO-friendly paginated links remain in the DOM; JS "load more" enhances them --}}
                <div x-show="next" class="mt-8 text-center"><button class="btn btn-outline" @click="more('grid')" :disabled="loading"><span x-text="loading ? 'Loading…' : 'Load more products'"></span></button></div>
                <noscript>{{ $products->links() }}</noscript>
                <div class="hidden">{{ $products->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
