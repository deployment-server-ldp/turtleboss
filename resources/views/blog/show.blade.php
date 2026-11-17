@extends('layouts.app')
@section('content')
<article class="container-x max-w-3xl py-8 sm:py-12">
    <p class="text-sm text-muted"><a href="{{ route('blog') }}" class="hover:underline">Blog</a>@if($post->category) / <a href="{{ route('blog.category', $post->category->slug) }}" class="hover:underline">{{ $post->category->name }}</a>@endif</p>
    <h1 class="mt-2 text-3xl leading-tight sm:text-4xl">{{ $post->title }}</h1>
    <p class="mt-2 text-sm text-muted">By {{ $post->author?->name ?? 'Turtle Boss' }} · {{ $post->published_at?->format('d M Y') }}</p>
    @if($post->featured_image)<img src="{{ img($post->featured_image) }}" alt="{{ $post->title }}" class="mt-6 w-full rounded-[18px]" fetchpriority="high">@endif
    <div class="prose-tb mt-8">{!! $post->content !!}</div>
    @if($post->tags->isNotEmpty())<p class="mt-8 flex flex-wrap gap-2 text-sm">@foreach($post->tags as $t)<a href="{{ route('blog.tag', $t->slug) }}" class="rounded-full bg-soft px-3 py-1 hover:bg-line">#{{ $t->name }}</a>@endforeach</p>@endif
</article>
@if($related->isNotEmpty())<section class="container-x pb-12"><h2 class="mb-5 text-2xl">Related articles</h2><div class="grid gap-6 sm:grid-cols-3">@foreach($related as $p)<a href="{{ route('blog.show', $p->slug) }}" class="group"><div class="aspect-[16/10] overflow-hidden rounded-[14px] bg-soft"><img src="{{ img($p->featured_image) }}" alt="" loading="lazy" class="h-full w-full object-cover"></div><h3 class="mt-2 text-base group-hover:underline">{{ $p->title }}</h3></a>@endforeach</div></section>@endif
@endsection
