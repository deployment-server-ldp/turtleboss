@extends('admin.layouts.admin')
@section('title', 'Media Library')
@section('content')
<form method="post" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="card mb-4 flex flex-wrap items-center gap-3 p-4">@csrf<input type="file" name="files[]" multiple accept="image/jpeg,image/png,image/webp,image/svg+xml" required class="text-sm"><button class="btn btn-primary btn-sm">Upload</button><span class="text-xs text-muted">JPG, PNG, WebP, SVG (sanitised). Raster images are resized and converted to WebP.</span>
    <form method="get" class="ml-auto"></form></form>
<form method="get" class="mb-4"><input name="q" value="{{ request('q') }}" placeholder="Search media…" class="field !w-64 !py-2"></form>
<div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">@forelse($items as $m)
    <div class="card overflow-hidden text-xs" x-data="{ o: false }"><button class="block aspect-square w-full bg-soft" @click="o = true"><img src="{{ $m->url }}" alt="{{ $m->alt }}" class="h-full w-full object-cover" loading="lazy"></button>
        <div class="p-2"><p class="truncate font-medium">{{ $m->title }}</p><p class="text-muted">{{ $m->width ? $m->width . '×' . $m->height . ' · ' : '' }}{{ round($m->size / 1024) }} KB</p>@unless(isset($used[$m->path]))<span class="badge bg-amber-50 text-amber-700">Unused</span>@endunless</div>
        <div x-show="o" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-navy/60 p-4" @click.self="o = false"><div class="w-full max-w-lg space-y-3 rounded-2xl bg-white p-5 text-sm"><img src="{{ $m->url }}" class="mx-auto max-h-60 rounded-lg" alt="">
            <input readonly value="{{ $m->url }}" class="field !py-2 text-xs" onclick="this.select()">
            <form method="post" action="{{ route('admin.media.update', $m) }}" enctype="multipart/form-data" class="space-y-2">@csrf @method('PUT')<input name="title" value="{{ $m->title }}" placeholder="Title" class="field !py-2"><input name="alt" value="{{ $m->alt }}" placeholder="ALT text" class="field !py-2"><label class="block text-xs">Replace file (all references update)<input type="file" name="replace" accept="image/*" class="block w-full"></label><button class="btn btn-primary btn-sm">Save</button></form>
            <form method="post" action="{{ route('admin.media.destroy', $m) }}" onsubmit="return confirm('Delete this file?')">@csrf @method('DELETE')<button class="text-red-600 underline">Delete file</button></form></div></div></div>
@empty<p class="col-span-full py-14 text-center text-muted">No media uploaded yet.</p>@endforelse</div><div class="mt-4">{{ $items->links() }}</div>
@endsection
