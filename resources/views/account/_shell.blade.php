@extends('layouts.app')
@section('content')
<div class="container-x py-8">
    <h1 class="mb-6 text-2xl sm:text-3xl">My Account</h1>
    <div class="grid gap-8 lg:grid-cols-[240px_1fr]">
        <nav class="no-scrollbar -mx-4 flex gap-2 overflow-x-auto px-4 lg:mx-0 lg:flex-col lg:px-0" aria-label="Account">
            @foreach(['account' => ['Overview', 'account'], 'account.orders' => ['My Orders', 'account/orders*'], 'account.wishlist' => null, 'account.addresses' => ['Saved Addresses', 'account/addresses'], 'account.profile' => ['Profile & Password', 'account/profile']] as $r => $v)
                @if($v)<a href="{{ route($r) }}" class="shrink-0 rounded-xl px-4 py-2.5 text-sm font-medium {{ request()->is($v[1]) ? 'bg-navy text-white' : 'bg-soft hover:bg-line' }}">{{ $v[0] }}</a>@endif
            @endforeach
            <a href="{{ route('wishlist') }}" class="shrink-0 rounded-xl bg-soft px-4 py-2.5 text-sm font-medium hover:bg-line">Wishlist</a>
            <form method="post" action="{{ route('logout') }}">@csrf<button class="shrink-0 rounded-xl px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50">Log out</button></form>
        </nav>
        <div>@yield('body')</div>
    </div>
</div>
@endsection
