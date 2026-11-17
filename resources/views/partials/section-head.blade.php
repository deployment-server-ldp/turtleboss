<div class="mb-6 flex items-end justify-between gap-4 sm:mb-8">
    <div>
        @if($s->title)<h2 class="text-2xl sm:text-3xl">{{ $s->title }}</h2>@endif
        @if($s->subtitle)<p class="mt-1 text-sm text-muted">{{ $s->subtitle }}</p>@endif
    </div>
    @if(!empty($link))<a href="{{ url($link) }}" class="shrink-0 text-sm font-semibold text-brand hover:underline">{{ $linkText ?? 'View all' }} →</a>@endif
</div>
