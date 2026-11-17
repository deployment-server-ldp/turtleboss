@extends('admin.layouts.admin')
@section('title', 'Menu Management')
@section('content')
<div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">@foreach($menus as $m)<a href="{{ route('admin.menus.edit', $m) }}" class="card p-5 hover:border-navy"><p class="font-semibold">{{ $m->name }}</p><p class="text-xs text-muted">Location: {{ $m->location }} · {{ $m->items_count }} items</p></a>@endforeach</div>
<form method="post" action="{{ route('admin.menus.store') }}" class="card mt-6 flex max-w-xl flex-wrap items-center gap-2 p-4">@csrf<input name="name" class="field !w-44 !py-2" placeholder="Menu name" required><input name="location" class="field !w-44 !py-2" placeholder="location_key" required><button class="btn btn-outline btn-sm">Create menu</button></form>
<p class="mt-3 text-xs text-muted">Built-in locations: <b>header</b> (main navigation), <b>footer_shop</b>, <b>footer_help</b>.</p>
@endsection
