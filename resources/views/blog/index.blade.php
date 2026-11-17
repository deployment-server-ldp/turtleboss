@extends('layouts.app')
@section('content')
<div class="container-x py-8 sm:py-12">
    <h1 class="text-3xl sm:text-4xl">{{ $heading }}</h1>
    <div class="mt-5 flex flex-wrap items-center gap-2">
        <a href="{{ route('blog') }}" class="rounded-full border border-line px-4 py-1.5 text-sm {{ request()->routeIs('blog') && !request('q') ? 'bg-navy text-white' : 'hover:border-navy' }}">All</a>
        @foreach($categories as $c)<a href="{{ route('blog.category', $c->slug) }}" class="rounded-full border border-line px-4 py-1.5 text-sm hover:border-navy">{{ $c->name }} ({{ $c->posts_count }})</a>@endforeach
        <form class="ml-auto" action="{{ route('blog') }}"><input name="q" value="{{ request('q') }}" placeholder="Search articles" class="field !py-2 !text-sm" aria-label="Search articles"></form>
    </div>
    <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($posts as $p)
            <a href="{{ route('blog.show', $p->slug) }}" class="group">
                <div class="aspect-[16/10] overflow-hidden rounded-[14px] bg-soft"><img src="{{ img($p->featured_image) }}" alt="{{ $p->title }}" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105"></div>
                <p class="mt-3 text-xs text-muted">{{ $p->category?->name }} · {{ $p->published_at?->format('d M Y') }}</p>
                <h2 class="mt-1 text-lg group-hover:underline">{{ $p->title }}</h2>
                <p class="mt-1 line-clamp-2 text-sm text-muted">{{ $p->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($p->content), 120) }}</p>
            </a>
        @empty<p class="text-muted sm:col-span-3">No articles yet.</p>@endforelse
    </div>
    {{ $posts->links() }}
</div>
@endsection
