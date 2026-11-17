@extends('layouts.app')
@section('content')
<div class="container-x py-8" x-data="checkoutPage('')" x-init="$store.cart.refresh()">
    <h1 class="mb-6 text-2xl sm:text-3xl">Shopping Cart</h1>
    <div x-show="$store.cart.loaded && !$store.cart.items.length" x-cloak class="card grid place-items-center p-14 text-center"><p class="text-lg font-semibold">Your cart is empty</p><a href="{{ route('shop') }}" class="btn btn-primary mt-5">Continue shopping</a></div>
    <div x-show="$store.cart.items.length" x-cloak class="grid gap-8 lg:grid-cols-[1fr_380px]">
        <ul class="divide-y divide-line rounded-[14px] border border-line">
            <template x-for="it in $store.cart.items" :key="it.id">
                <li class="flex gap-4 p-4">
                    <a :href="it.url"><img :src="it.image" :alt="it.name" class="h-24 w-24 rounded-xl bg-soft object-cover"></a>
                    <div class="min-w-0 flex-1">
                        <div class="flex justify-between gap-3"><a :href="it.url" class="font-medium hover:underline" x-text="it.name"></a><b class="shrink-0" x-text="tbMoney(it.line_total)"></b></div>
                        <template x-if="it.options.length > 1"><select class="mt-1 rounded-lg border border-line py-1 pl-2 pr-7 text-xs" @change="$store.cart.changeVariant(it.id, $event.target.value)"><template x-for="o in it.options" :key="o.id"><option :value="o.id" :selected="o.label === it.label" x-text="o.label"></option></template></select></template>
                        <template x-if="it.options.length <= 1"><p class="text-xs text-muted" x-text="it.label"></p></template>
                        <p class="mt-1 text-sm text-muted"><span x-text="tbMoney(it.unit)"></span> <span class="line-through" x-show="it.regular > it.unit" x-text="tbMoney(it.regular)"></span></p>
                        <div class="mt-3 flex items-center justify-between">
                            <div class="inline-flex items-center rounded-lg border border-line"><button class="h-9 w-9" @click="$store.cart.setQty(it.id, it.qty - 1)" aria-label="Decrease">−</button><span class="w-8 text-center text-sm" x-text="it.qty"></span><button class="h-9 w-9" @click="$store.cart.setQty(it.id, it.qty + 1)" aria-label="Increase">+</button></div>
                            <button class="text-sm text-muted underline hover:text-red-600" @click="$store.cart.remove(it.id)">Remove</button>
                        </div>
                    </div>
                </li>
            </template>
        </ul>
        <aside class="card h-fit p-5 lg:sticky lg:top-24">
            <h2 class="mb-4 text-lg">Order Summary</h2>
            <div class="mb-4"><template x-if="!$store.cart.coupon"><form @submit.prevent="apply()" class="flex gap-2"><input x-model="coupon" class="field !py-2.5 uppercase" placeholder="Coupon code"><button class="btn btn-outline btn-sm">Apply</button></form></template>
                <template x-if="$store.cart.coupon"><div class="flex items-center justify-between rounded-xl bg-green-50 px-3 py-2 text-sm text-green-800"><span>Coupon <b x-text="$store.cart.coupon"></b> applied</span><button @click="removeCoupon()" class="text-xs underline">Remove</button></div></template></div>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd x-text="tbMoney($store.cart.subtotal)"></dd></div>
                <div class="flex justify-between text-green-700" x-show="$store.cart.discount > 0"><dt>Discount</dt><dd x-text="'− ' + tbMoney($store.cart.discount)"></dd></div>
                <div class="flex justify-between"><dt class="text-muted">Shipping</dt><dd x-text="$store.cart.shipping > 0 ? tbMoney($store.cart.shipping) : 'Free'"></dd></div>
                <div class="flex justify-between border-t border-line pt-3 text-base font-bold"><dt>Total</dt><dd x-text="tbMoney($store.cart.total)"></dd></div>
            </dl>
            <p class="mt-2 text-xs text-muted">Shipping may change by city at checkout.</p>
            <a href="{{ route('checkout') }}" class="btn btn-primary mt-5 w-full">Proceed to Checkout</a>
            <a href="{{ route('shop') }}" class="mt-3 block text-center text-sm text-muted hover:underline">Continue shopping</a>
        </aside>
    </div>
</div>
@endsection
