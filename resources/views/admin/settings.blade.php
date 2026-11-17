@extends('admin.layouts.admin')
@section('title', $g['title'])
@section('content')
<div class="mx-auto max-w-3xl">
    @if(!empty($g['note']))<div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">{{ $g['note'] }}</div>@endif
    <form method="post" enctype="multipart/form-data" action="{{ route('admin.settings.update', $group) }}" class="card space-y-5 p-5 sm:p-6">@csrf @method('PUT')
        @foreach($g['fields'] as [$k, $label, $t])
            @php $val = old($k, setting($k)); @endphp
            @if($t === 'checkbox')<label class="flex items-center gap-3"><input type="checkbox" name="{{ $k }}" value="1" @checked($val === '1' || $val === 1 || $val === true) class="h-4 w-4 rounded border-line"><span class="font-medium">{{ $label }}</span></label>
            @else<div><label class="label">{{ $label }}</label>
                @if($t === 'textarea')<textarea name="{{ $k }}" rows="4" class="field">{{ $val }}</textarea>
                @elseif($t === 'image')@if($val)<div class="mb-2 flex items-center gap-3"><img src="{{ img($val) }}" class="h-14 max-w-[200px] rounded-lg border border-line bg-soft object-contain p-1" alt=""><label class="text-xs text-red-600"><input type="checkbox" name="{{ $k }}_remove" value="1"> Remove</label></div>@endif<input type="file" name="{{ $k }}" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="block w-full text-sm">
                @elseif($t === 'password')<input type="password" name="{{ $k }}" class="field" placeholder="{{ setting($k) ? '•••••••• (saved — leave empty to keep)' : '' }}" autocomplete="new-password">
                @else<input type="{{ $t === 'number' ? 'number' : 'text' }}" step="any" name="{{ $k }}" value="{{ $t === 'password' ? '' : $val }}" class="field">@endif</div>@endif
        @endforeach
        <button class="btn btn-primary">Save settings</button>
    </form>
    @if(($g['extra'] ?? null) === 'shipping')
        <div class="mt-6"><div class="mb-2 flex items-center justify-between"><h2 class="text-base">Shipping rules (city-based charges)</h2><a href="{{ route('admin.shipping-rules.create') }}" class="btn btn-primary btn-sm">+ Add rule</a></div>
            <div class="card divide-y divide-line">@forelse($rules as $r)<a href="{{ route('admin.shipping-rules.edit', $r) }}" class="flex justify-between gap-3 p-3 hover:bg-soft"><span>{{ $r->name }} <span class="text-xs text-muted">· {{ $r->city ?: 'All cities' }}</span></span><span>{{ money($r->rate) }} {{ $r->free_over ? '· free over ' . money($r->free_over) : '' }} {{ $r->is_active ? '' : '(off)' }}</span></a>@empty<p class="p-6 text-center text-sm text-muted">No rules: the default flat rate above applies. A city rule beats an all-city rule; lower priority numbers are checked first.</p>@endforelse</div></div>
    @endif
</div>
@endsection
