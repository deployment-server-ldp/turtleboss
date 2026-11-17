<nav aria-label="Breadcrumb" class="mb-4 text-xs text-muted"><ol class="flex flex-wrap items-center gap-1.5">
    @foreach($crumbs as $c)
        <li class="flex items-center gap-1.5">@if(!$loop->last)<a href="{{ $c['url'] }}" class="hover:text-navy hover:underline">{{ $c['name'] }}</a><span aria-hidden="true">/</span>@else<span class="text-navy" aria-current="page">{{ $c['name'] }}</span>@endif</li>
    @endforeach
</ol></nav>
