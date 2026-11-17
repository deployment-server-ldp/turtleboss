@props(['class' => 'h-9 w-auto'])
@php
    $custom = setting('logo');
    $src = $custom ? img($custom) : (file_exists(public_path('images/logo.png')) ? asset('images/logo.png') : (file_exists(public_path('images/logo.svg')) ? asset('images/logo.svg') : null));
@endphp
@if($src)
    <img src="{{ $src }}" alt="{{ setting('site_name', 'Turtle Boss') }}" class="{{ $class }}" width="160" height="40">
@else
    {{-- Fallback only until the official logo is uploaded (Admin → General Settings → Logo, or public/images/logo.png) --}}
    <span class="text-xl font-extrabold tracking-tight text-navy">TURTLE <span class="text-brand">BOSS</span></span>
@endif
