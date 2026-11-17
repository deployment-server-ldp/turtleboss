@props(['rating' => 0, 'count' => null, 'size' => 'w-3.5 h-3.5'])
<span class="inline-flex items-center gap-1" @if($count !== null) aria-label="Rated {{ $rating }} out of 5" @endif>
    <span class="inline-flex">
        @for($i = 1; $i <= 5; $i++)
            <svg class="{{ $size }} {{ $rating >= $i - 0.25 ? 'text-amber-400' : ($rating >= $i - 0.75 ? 'text-amber-300' : 'text-gray-200') }}" viewBox="0 0 20 20" fill="currentColor"><path d="M10 1.5l2.6 5.5 6 .8-4.4 4.2 1.1 6L10 15.1 4.7 18l1.1-6L1.4 7.8l6-.8L10 1.5z"/></svg>
        @endfor
    </span>
    @if($count !== null)<span class="text-xs text-muted">({{ $count }})</span>@endif
</span>
