@extends('layouts.app')
@section('content')
@if($page->banner)<div class="h-40 overflow-hidden bg-soft sm:h-64"><img src="{{ img($page->banner) }}" alt="{{ $page->title }}" class="h-full w-full object-cover"></div>@endif
<div class="container-x max-w-4xl py-8 sm:py-12">
    @include('partials.breadcrumbs')
    <h1 class="mb-6 text-3xl sm:text-4xl">{{ $page->title }}</h1>
    @if($page->template === 'faq')
        <div class="prose-tb">{!! $page->content !!}</div>
    @else
        <div class="prose-tb">{!! $page->content !!}</div>
    @endif
    @if($page->template === 'contact')
        <div class="mt-10 grid gap-8 md:grid-cols-[1fr_320px]">
            <form method="post" action="{{ route('contact') }}" class="card space-y-4 p-6">@csrf
                <h2 class="text-xl">Send us a message</h2>
                @if($errors->any())<p class="rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</p>@endif
                <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
                <div class="grid gap-4 sm:grid-cols-2"><div><label class="label">Name *</label><input name="name" class="field" value="{{ old('name') }}" required></div><div><label class="label">Phone</label><input name="phone" class="field" value="{{ old('phone') }}"></div></div>
                <div><label class="label">Email *</label><input name="email" type="email" class="field" value="{{ old('email') }}" required></div>
                <div><label class="label">Message *</label><textarea name="message" rows="5" class="field" required>{{ old('message') }}</textarea></div>
                <button class="btn btn-primary">Send message</button>
            </form>
            <div class="card h-fit space-y-3 p-6 text-sm">
                <h2 class="text-lg">Get in touch</h2>
                @if(setting('contact_email'))<p><span class="text-muted">Email</span><br><a href="mailto:{{ setting('contact_email') }}" class="font-medium">{{ setting('contact_email') }}</a></p>@endif
                @if(setting('contact_phone'))<p><span class="text-muted">Phone</span><br><a href="tel:{{ setting('contact_phone') }}" class="font-medium">{{ setting('contact_phone') }}</a></p>@endif
                @if(setting('whatsapp_number'))<p><span class="text-muted">WhatsApp</span><br><a href="https://wa.me/{{ preg_replace('/\D/', '', setting('whatsapp_number')) }}" class="font-medium">Chat with us</a></p>@endif
                @if(setting('contact_address'))<p><span class="text-muted">Address</span><br>{{ setting('contact_address') }}</p>@endif
            </div>
        </div>
    @endif
</div>
@endsection
