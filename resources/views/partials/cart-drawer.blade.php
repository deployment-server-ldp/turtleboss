<div x-data x-show="$store.ui.drawer" x-cloak class="fixed inset-0 z-[100]" @keydown.escape.window="$store.ui.drawer && ($store.ui.drawer = false, $store.ui.lock(false))" role="dialog" aria-label="Shopping cart">
    <div class="absolute inset-0 bg-navy/50" @click="$store.ui.drawer = false; $store.ui.lock(false)" x-transition.opacity></div>
    <aside class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col bg-white shadow-2xl" x-show="$store.ui.drawer" x-transition:enter="transition duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition duration-200" x-transition:leave-end="translate-x-full">
        <div class="flex items-center justify-between border-b border-line p-4">
            <h2 class="text-lg">Your Cart <span class="text-sm font-normal text-muted" x-text="'(' + $store.cart.count + ')'"></span></h2>
            <button @click="$store.ui.drawer = false; $store.ui.lock(false)" class="grid h-9 w-9 place-items-center rounded-full hover:bg-soft" aria-label="Close cart">✕</button>
        </div>
        <template x-if="$store.cart.freeThreshold > 0 && $store.cart.items.length">
            <div class="border-b border-line bg-soft px-4 py-3 text-xs">
                <p x-show="$store.cart.remainingForFree > 0">Add <b x-text="tbMoney($store.cart.remainingForFree)"></b> more for <b>free shipping</b></p>
                <p x-show="$store.cart.remainingForFree <= 0" class="font-semibold text-green-700">🎉 You've unlocked free shipping!</p>
                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-line"><div class="h-full rounded-full bg-navy transition-all" :style="'width:' + Math.min(100, (($store.cart.subtotal - $store.cart.discount) / $store.cart.freeThreshold) * 100) + '%'"></div></div>
            </div>
        </template>
        <div class="flex-1 overflow-y-auto p-4">
            <div x-show="!$store.cart.items.length" class="grid h-full place-items-center text-center">
                <div><p class="text-4xl">🛍️</p><p class="mt-3 font-semibold">Your cart is empty</p><a href="{{ route('shop') }}" class="btn btn-primary mt-4">Start shopping</a></div>
            </div>
            <ul class="space-y-4">
                <template x-for="it in $store.cart.items" :key="it.id">
                    <li class="flex gap-3">
                        <a :href="it.url"><img :src="it.image" :alt="it.name" class="h-20 w-20 rounded-xl bg-soft object-cover" loading="lazy"></a>
                        <div class="min-w-0 flex-1">
                            <a :href="it.url" class="line-clamp-2 text-sm font-medium" x-text="it.name"></a>
                            <p class="text-xs text-muted" x-show="it.label" x-text="it.label"></p>
                            <div class="mt-1 flex items-baseline gap-2"><span class="text-sm font-bold" x-text="tbMoney(it.unit)"></span><span class="text-xs text-muted line-through" x-show="it.regular > it.unit" x-text="tbMoney(it.regular)"></span></div>
                            <div class="mt-2 flex items-center justify-between">
                                <div class="inline-flex items-center rounded-lg border border-line">
                                    <button class="h-8 w-8" @click="$store.cart.setQty(it.id, it.qty - 1)" aria-label="Decrease">−</button>
                                    <span class="w-7 text-center text-sm" x-text="it.qty"></span>
                                    <button class="h-8 w-8" @click="$store.cart.setQty(it.id, it.qty + 1)" aria-label="Increase">+</button>
                                </div>
                                <button class="text-xs text-muted underline hover:text-red-600" @click="$store.cart.remove(it.id)">Remove</button>
                            </div>
                        </div>
                    </li>
                </template>
            </ul>
        </div>
        <div class="space-y-3 border-t border-line p-4" x-show="$store.cart.items.length">
            <div class="flex justify-between text-sm"><span class="text-muted">Subtotal</span><b x-text="tbMoney($store.cart.subtotal)"></b></div>
            <div class="flex justify-between text-sm text-green-700" x-show="$store.cart.discount > 0"><span>Discount <span x-text="$store.cart.coupon ? '(' + $store.cart.coupon + ')' : ''"></span></span><b x-text="'− ' + tbMoney($store.cart.discount)"></b></div>
            <p class="text-xs text-muted">Shipping calculated at checkout.</p>
            <a href="{{ route('checkout') }}" class="btn btn-primary w-full">Checkout</a>
            <a href="{{ route('cart') }}" class="btn btn-outline w-full">View full cart</a>
        </div>
    </aside>
</div>
