@extends('admin.layouts.admin')
@section('title', 'Backup & Maintenance')
@section('content')
<div class="grid gap-6 lg:grid-cols-2">
    <div class="card p-5"><h2 class="text-base">Database backups</h2><p class="mt-1 text-sm text-muted">Driver: <b>{{ $driver }}</b>. Backups are stored in <code>storage/app/private/backups</code> (last 14 kept). A daily backup runs at 03:00 when the Laravel scheduler cron is enabled.</p>
        <form method="post" action="{{ route('admin.backup.create') }}" class="mt-3">@csrf<button class="btn btn-primary btn-sm">Create backup now</button></form>
        <ul class="mt-4 divide-y divide-line text-sm">@forelse($files as $f)<li class="flex items-center justify-between gap-3 py-2"><span>{{ $f['name'] }}<span class="block text-xs text-muted">{{ $f['at']->format('d M Y H:i') }} · {{ round($f['size'] / 1024) }} KB</span></span><span class="flex gap-3"><a href="{{ route('admin.backup.download', $f['name']) }}" class="text-brand">Download</a><form method="post" action="{{ route('admin.backup.delete', $f['name']) }}" onsubmit="return confirm('Delete backup?')">@csrf @method('DELETE')<button class="text-red-600">Delete</button></form></span></li>@empty<li class="py-4 text-muted">No backups yet.</li>@endforelse</ul>
        <p class="mt-3 text-xs text-muted">Uploaded images live in <code>storage/app/public</code> — back this folder up too (host snapshot or rsync).</p></div>
    <div class="space-y-6">
        <div class="card p-5"><h2 class="text-base">Cache</h2><p class="mt-1 text-sm text-muted">Clear application, view and sitemap caches.</p><form method="post" action="{{ route('admin.backup.cache') }}" class="mt-3">@csrf<button class="btn btn-outline btn-sm">Clear caches</button></form></div>
        <div class="card p-5"><h2 class="text-base">Maintenance mode</h2><p class="mt-1 text-sm text-muted">Currently: <b>{{ $maintenance ? 'ON (visitors see 503)' : 'OFF (site is live)' }}</b></p><form method="post" action="{{ route('admin.backup.mode') }}" class="mt-3" onsubmit="return confirm('Change maintenance mode?')">@csrf<input type="hidden" name="mode" value="{{ $maintenance ? 'up' : 'down' }}"><button class="btn btn-outline btn-sm">{{ $maintenance ? 'Bring site back online' : 'Enable maintenance mode' }}</button></form></div>
    </div>
</div>
@endsection
