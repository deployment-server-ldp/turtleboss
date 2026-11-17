<div x-data="productPage({{ \Illuminate\Support\Js::from($config) }})" class="grid gap-6 p-5 sm:p-8 md:grid-cols-2">
    <div>
        <div class="aspect-square overflow-hidden rounded-2xl bg-soft"><img :src="gallery[main]?.url || '{{ $p->image_url }}'" alt="{{ $p->name }}" class="h-full w-full object-cover"></div>
        <div class="mt-3 flex gap-2 overflow-x-auto no-scrollbar"><template x-for="(g, i) in gallery" :key="i"><button @click="main = i" class="h-16 w-16 shrink-0 overflow-hidden rounded-lg border-2" :class="main === i ? 'border-navy' : 'border-transparent'"><img :src="g.url" alt="" class="h-full w-full object-cover"></button></template></div>
    </div>
    <div>
        @if($p->brand)<p class="text-xs uppercase tracking-wide text-muted">{{ $p->brand }}</p>@endif
        <h2 class="text-2xl">{{ $p->name }}</h2>
        @if($p->rating_count)<div class="mt-1"><x-stars :rating="$p->rating_avg" :count="$p->rating_count"/></div>@endif
        @include('product._price')
        @if($p->short_description)<p class="mt-3 text-sm text-muted">{{ $p->short_description }}</p>@endif
        @include('product._buybox')
        <a href="{{ $p->url }}" class="mt-4 inline-block text-sm font-semibold text-brand hover:underline">View full details →</a>
    </div>
</div>
