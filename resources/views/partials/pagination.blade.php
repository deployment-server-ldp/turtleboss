@if ($paginator->hasPages())
<nav class="mt-10 flex flex-wrap items-center justify-center gap-1.5" role="navigation" aria-label="Pagination">
    @if ($paginator->onFirstPage())<span class="rounded-lg border border-line px-3.5 py-2 text-sm text-gray-300">Prev</span>
    @else<a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="rounded-lg border border-line px-3.5 py-2 text-sm hover:border-navy">Prev</a>@endif
    @foreach ($elements as $element)
        @if (is_string($element))<span class="px-2 text-muted">{{ $element }}</span>@endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())<span aria-current="page" class="rounded-lg bg-navy px-3.5 py-2 text-sm font-semibold text-white">{{ $page }}</span>
                @else<a href="{{ $url }}" class="rounded-lg border border-line px-3.5 py-2 text-sm hover:border-navy">{{ $page }}</a>@endif
            @endforeach
        @endif
    @endforeach
    @if ($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next" class="rounded-lg border border-line px-3.5 py-2 text-sm hover:border-navy">Next</a>
    @else<span class="rounded-lg border border-line px-3.5 py-2 text-sm text-gray-300">Next</span>@endif
</nav>
@endif
