@extends('admin.layouts.admin')
@section('title', 'Homepage Builder')
@section('content')
<p class="mb-4 max-w-2xl text-sm text-muted">Enable, disable, edit and reorder the sections shown on the homepage. The announcement bar and hero slider are managed in <a href="{{ route('admin.settings.show', 'general') }}" class="text-brand underline">General Settings</a> and <a href="{{ route('admin.sliders.index') }}" class="text-brand underline">Slider Management</a>.</p>
<form method="post" action="{{ route('admin.homepage.reorder') }}" x-data="{ items: @js($sections->pluck('id')), move(i, d) { const j = i + d; if (j < 0 || j >= this.items.length) return; [this.items[i], this.items[j]] = [this.items[j], this.items[i]]; } }">@csrf
    <template x-for="id in items"><input type="hidden" name="order[]" :value="id"></template>
    <ul class="space-y-2">@foreach($sections as $s)
        <li class="card flex items-center gap-3 p-3" :style="'order:' + items.indexOf({{ $s->id }})"><div class="flex flex-col"><button type="button" @click="move(items.indexOf({{ $s->id }}), -1)" class="px-2 leading-none text-muted hover:text-navy" aria-label="Up">▲</button><button type="button" @click="move(items.indexOf({{ $s->id }}), 1)" class="px-2 leading-none text-muted hover:text-navy" aria-label="Down">▼</button></div>
            <div class="min-w-0 flex-1"><p class="font-semibold">{{ $s->title ?: $types[$s->type] }}</p><p class="text-xs text-muted">{{ $types[$s->type] ?? $s->type }}</p></div>
            <span class="badge {{ $s->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $s->is_active ? 'Enabled' : 'Disabled' }}</span>
            <button type="submit" form="tg{{ $s->id }}" class="text-sm text-muted hover:text-navy">{{ $s->is_active ? 'Disable' : 'Enable' }}</button><a href="{{ route('admin.homepage.edit', $s) }}" class="text-sm font-medium text-brand">Edit</a><button form="rm{{ $s->id }}" onclick="return confirm('Remove this section?')" class="text-sm text-red-600">Remove</button></li>@endforeach</ul>
    <div class="mt-4 flex gap-2" style="flex-wrap: wrap"><button class="btn btn-primary btn-sm">Save order</button></div>
</form>
@foreach($sections as $s)<form id="tg{{ $s->id }}" method="post" action="{{ route('admin.homepage.toggle', $s) }}" class="hidden">@csrf</form><form id="rm{{ $s->id }}" method="post" action="{{ route('admin.homepage.destroy', $s) }}" class="hidden">@csrf @method('DELETE')</form>@endforeach
<form method="post" action="{{ route('admin.homepage.store') }}" class="card mt-6 flex max-w-xl flex-wrap items-center gap-2 p-4">@csrf<span class="font-medium">Add section:</span><select name="type" class="field !w-60 !py-2">@foreach($types as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select><button class="btn btn-outline btn-sm">Add</button></form>
@endsection
