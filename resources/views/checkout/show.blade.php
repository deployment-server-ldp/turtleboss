@extends('layouts.app')
@section('content')
@php $def = $addresses->firstWhere('is_default', true) ?? $addresses->first(); @endphp
<div class="container-x py-8" x-data="checkoutPage('{{ old('city', $def?->city) }}')" x-init="$store.cart.refresh()">
    <h1 class="mb-6 text-2xl sm:text-3xl">Checkout</h1>
    @if($errors->has('cart'))<div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ $errors->first('cart') }}</div>@endif
    @guest<p class="mb-5 rounded-xl bg-soft p-3 text-sm">Checking out as a guest. <a href="{{ route('login') }}" class="font-semibold text-brand underline">Log in</a> or <a href="{{ route('register') }}" class="font-semibold text-brand underline">create an account</a> to track orders & save addresses.</p>@endguest
    <form method="post" action="{{ route('checkout.place') }}" class="grid gap-8 lg:grid-cols-[1fr_400px]" x-data="{ pay: '{{ old('payment_method', array_key_first($methods) ?? 'cod') }}' }">
        @csrf
        <div class="space-y-6">
            <section class="card p-5 sm:p-6">
                <h2 class="mb-4 text-lg">Contact & delivery</h2>
                @if($addresses->isNotEmpty())
                    <div class="mb-4"><label class="label">Use a saved address</label>
                        <select class="field" @change="const a = JSON.parse($event.target.value || 'null'); if (a) { $refs.name.value=a.name; $refs.phone.value=a.phone; $refs.address.value=a.address; $refs.city.value=a.city; $refs.province.value=a.province||''; $refs.postal.value=a.postal_code||''; $dispatch('city', a.city) }">
                            <option value="">Choose…</option>@foreach($addresses as $a)<option value="{{ json_encode($a->only(['name', 'phone', 'address', 'city', 'province', 'postal_code'])) }}">{{ $a->label ?: $a->address }} – {{ $a->city }}</option>@endforeach</select></div>
                @endif
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label" for="name">Full name *</label><input x-ref="name" id="name" name="name" class="field" value="{{ old('name', $user?->name) }}" required autocomplete="name">@error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="label" for="phone">Phone *</label><input x-ref="phone" id="phone" name="phone" inputmode="tel" class="field" value="{{ old('phone', $user?->phone ?? $def?->phone) }}" required autocomplete="tel" placeholder="03XX-XXXXXXX">@error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div class="sm:col-span-2"><label class="label" for="email">Email *</label><input id="email" type="email" name="email" class="field" value="{{ old('email', $user?->email) }}" required autocomplete="email">@error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div class="sm:col-span-2"><label class="label" for="address">Complete address *</label><input x-ref="address" id="address" name="address" class="field" value="{{ old('address', $def?->address) }}" required autocomplete="street-address" placeholder="House / street / area">@error('address')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="label" for="city">City *</label>
                        <select x-ref="city" id="city" name="city" class="field" required x-model="city" @change="requote()" @city.window="city = $event.detail; requote()"><option value="">Select city</option>@foreach($cities as $c)<option @selected(old('city', $def?->city) === $c)>{{ $c }}</option>@endforeach</select>@error('city')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="label" for="province">Province</label><select x-ref="province" id="province" name="province" class="field"><option value="">Select</option>@foreach($provinces as $p)<option @selected(old('province', $def?->province) === $p)>{{ $p }}</option>@endforeach</select></div>
                    <div><label class="label" for="postal">Postal code</label><input x-ref="postal" id="postal" name="postal_code" class="field" value="{{ old('postal_code', $def?->postal_code) }}" inputmode="numeric"></div>
                    <div class="sm:col-span-2"><label class="label" for="notes">Order notes</label><textarea id="notes" name="notes" rows="2" class="field" placeholder="Anything we should know?">{{ old('notes') }}</textarea></div>
                    @auth<label class="flex items-center gap-2 text-sm sm:col-span-2"><input type="checkbox" name="save_address" value="1" class="rounded border-line text-navy" checked> Save this address for next time</label>@endauth
                </div>
            </section>
            <section class="card p-5 sm:p-6">
                <h2 class="mb-4 text-lg">Payment method</h2>
                <div class="space-y-3">
                    @forelse($methods as $k => $m)
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition" :class="pay === '{{ $k }}' ? 'border-navy bg-navy-50' : 'border-line'">
                            <input type="radio" name="payment_method" value="{{ $k }}" x-model="pay" class="mt-1 text-navy"><span><span class="block font-semibold">{{ $m['label'] }}</span><span class="text-sm text-muted">{{ $m['desc'] }}</span>
                            @if($k === 'bank_transfer')<span x-show="pay === 'bank_transfer'" x-cloak class="mt-2 block whitespace-pre-line rounded-lg bg-white p-3 text-sm">{{ setting('bank_details') }}</span>@endif</span>
                        </label>
                    @empty<p class="text-sm text-red-600">No payment methods are enabled. Please contact the store.</p>@endforelse
                </div>
                @error('payment_method')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </section>
        </div>

        <aside class="card h-fit p-5 lg:sticky lg:top-24">
            <h2 class="mb-4 text-lg">Your order</h2>
            <ul class="mb-4 max-h-72 space-y-3 overflow-y-auto">
                <template x-for="it in $store.cart.items" :key="it.id"><li class="flex gap-3 text-sm"><div class="relative"><img :src="it.image" class="h-14 w-14 rounded-lg bg-soft object-cover" alt=""><span class="absolute -right-1.5 -top-1.5 grid h-5 min-w-5 place-items-center rounded-full bg-navy px-1 text-[10px] text-white" x-text="it.qty"></span></div><div class="min-w-0 flex-1"><p class="truncate font-medium" x-text="it.name"></p><p class="text-xs text-muted" x-text="it.label"></p></div><b x-text="tbMoney(it.line_total)"></b></li></template>
            </ul>
            <div class="mb-4"><template x-if="!$store.cart.coupon"><div class="flex gap-2"><input x-model="coupon" @keydown.enter.prevent="apply()" class="field !py-2.5 uppercase" placeholder="Coupon code"><button type="button" class="btn btn-outline btn-sm" @click="apply()">Apply</button></div></template>
                <template x-if="$store.cart.coupon"><div class="flex items-center justify-between rounded-xl bg-green-50 px-3 py-2 text-sm text-green-800"><span><b x-text="$store.cart.coupon"></b> applied</span><button type="button" @click="removeCoupon()" class="text-xs underline">Remove</button></div></template></div>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd x-text="tbMoney($store.cart.subtotal)"></dd></div>
                <div class="flex justify-between text-green-700" x-show="$store.cart.discount > 0"><dt>Discount</dt><dd x-text="'− ' + tbMoney($store.cart.discount)"></dd></div>
                <div class="flex justify-between"><dt class="text-muted">Shipping <span class="text-xs" x-text="city ? '(' + shippingLabel + ')' : ''"></span></dt><dd x-text="!city ? 'Select city' : (shipping > 0 ? tbMoney(shipping) : 'Free')"></dd></div>
                <div class="flex justify-between border-t border-line pt-3 text-lg font-bold"><dt>Total</dt><dd x-text="tbMoney(total)"></dd></div>
            </dl>
            <button class="btn btn-primary mt-5 h-12 w-full" @disabled(empty($methods))>Place Order</button>
            <p class="mt-3 text-center text-xs text-muted">🔒 Your information is secure. Prices are verified again when you place the order.</p>
        </aside>
    </form>
</div>
@endsection
