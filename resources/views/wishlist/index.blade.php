@extends('layouts.app')
@section('content')
<div class="container-x py-8" x-data>
    <h1 class="mb-6 text-2xl sm:text-3xl">My Wishlist</h1>
    @if($products->isEmpty())<div class="card grid place-items-center p-14 text-center"><p class="text-lg font-semibold">Your wishlist is empty</p><p class="mt-1 text-sm text-muted">Tap the heart on any product to save it here.</p><a href="{{ route('shop') }}" class="btn btn-primary mt-5">Browse products</a></div>
    @else
    <div class="grid grid-cols-2 gap-x-3 gap-y-7 sm:gap-x-5 lg:grid-cols-4">
        @foreach($products as $p)
            <div class="flex flex-col"><x-product-card :product="$p"/>
                @if($p->in_stock)<button class="btn btn-primary btn-sm mt-2 hidden lg:inline-flex" @if($p->has_variants) @click="location.href='{{ $p->url }}'" @else @click="$store.cart.add({{ $p->id }}).then(ok => ok && $store.wish.toggle({{ $p->id }}).then(() => location.reload()))" @endif>{{ $p->has_variants ? 'Select options' : 'Move to cart' }}</button>@endif</div>
        @endforeach
    </div>@endif
</div>
@endsection
