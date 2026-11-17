@php $msgs = collect(preg_split('/\R/', (string) setting('announcement_messages', '')))->map(fn ($x) => trim($x))->filter()->values(); @endphp
@if(setting('announcement_enabled', '1') === '1' && $msgs->isNotEmpty())
<div class="bg-navy text-center text-xs font-medium text-white" x-data="rotator({{ $msgs->count() }})">
    <div class="relative h-9 overflow-hidden">
        @foreach($msgs as $i => $m)
            <p class="absolute inset-0 flex items-center justify-center px-4 transition-all duration-500" :class="i === {{ $i }} ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'">{{ $m }}</p>
        @endforeach
    </div>
</div>
@endif
