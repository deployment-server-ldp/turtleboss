@extends('admin.layouts.admin')
@section('title', ($m->exists ? 'Edit ' : 'New ') . $singular)
@section('content')
<form method="post" enctype="multipart/form-data" action="{{ $m->exists ? route($route . '.update', $m->getKey()) : route($route . '.store') }}" class="mx-auto max-w-4xl space-y-6">
    @csrf @if($m->exists) @method('PUT') @endif
    <div class="card space-y-5 p-5 sm:p-6">
        @foreach($fields as $f)
            @php $n = $f['name']; $t = $f['type'] ?? 'text'; $val = old($n, !empty($f['virtual']) ? ($f['default'] ?? null) : ($m->exists ? $m->{$n} : ($f['default'] ?? null))); @endphp
            @if($t === 'checkbox')
                <label class="flex items-center gap-3"><input type="hidden" name="{{ $n }}" value="0"><input type="checkbox" name="{{ $n }}" value="1" @checked($val) class="h-4 w-4 rounded border-line text-navy"><span class="font-medium">{{ $f['label'] }}</span></label>
            @else
                <div>
                    <label class="label" for="f_{{ $n }}">{{ $f['label'] }}</label>
                    @switch($t)
                        @case('textarea')<textarea id="f_{{ $n }}" name="{{ $n }}" rows="{{ $f['rows'] ?? 4 }}" class="field">{{ $val }}</textarea>@break
                        @case('richtext')
                            <div x-data="richText" class="overflow-hidden rounded-xl border border-line focus-within:border-navy">
                                <div class="flex flex-wrap gap-1 border-b border-line bg-soft p-1.5 text-sm">
                                    @foreach([['bold', 'B'], ['italic', 'I'], ['underline', 'U'], ['insertUnorderedList', '• List'], ['insertOrderedList', '1. List'], ['removeFormat', 'Clear']] as [$c, $l])<button type="button" @click="cmd('{{ $c }}')" class="rounded px-2.5 py-1 hover:bg-white">{{ $l }}</button>@endforeach
                                    <button type="button" @click="cmd('formatBlock', 'h2')" class="rounded px-2.5 py-1 hover:bg-white">H2</button><button type="button" @click="cmd('formatBlock', 'h3')" class="rounded px-2.5 py-1 hover:bg-white">H3</button><button type="button" @click="cmd('formatBlock', 'p')" class="rounded px-2.5 py-1 hover:bg-white">P</button>
                                    <button type="button" @click="link()" class="rounded px-2.5 py-1 hover:bg-white">Link</button><button type="button" @click="image()" class="rounded px-2.5 py-1 hover:bg-white">Image</button>
                                </div>
                                <div x-ref="area" contenteditable="true" @input="sync()" @blur="sync()" class="prose-tb min-h-[200px] max-w-none p-4 outline-none"></div>
                                <textarea x-ref="input" name="{{ $n }}" class="hidden">{{ $val }}</textarea>
                            </div>@break
                        @case('select')
                            <select id="f_{{ $n }}" name="{{ $n }}" class="field">@if(isset($f['empty']))<option value="">{{ $f['empty'] }}</option>@endif @foreach($f['options'] as $k => $l)<option value="{{ $k }}" @selected((string) $val === (string) $k)>{{ $l }}</option>@endforeach</select>@break
                        @case('multiselect')
                            <select id="f_{{ $n }}" name="{{ $n }}[]" multiple size="6" class="field">@foreach($f['options'] as $k => $l)<option value="{{ $k }}" @selected(in_array($k, (array) $val))>{{ $l }}</option>@endforeach</select><p class="mt-1 text-xs text-muted">Hold Ctrl / Cmd to select several.</p>@break
                        @case('image')
                            @if($m->exists && $m->{$n})<div class="mb-2 flex items-center gap-3"><img src="{{ img($m->{$n}) }}" class="h-20 w-32 rounded-lg border border-line object-cover" alt=""><label class="flex items-center gap-2 text-xs text-red-600"><input type="checkbox" name="{{ $n }}_remove" value="1"> Remove</label></div>@endif
                            <input id="f_{{ $n }}" type="file" name="{{ $n }}" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-navy file:px-4 file:py-2 file:text-white">@break
                        @case('datetime')<input id="f_{{ $n }}" type="datetime-local" name="{{ $n }}" value="{{ $val ? \Illuminate\Support\Carbon::parse($val)->format('Y-m-d\TH:i') : '' }}" class="field">@break
                        @case('number')<input id="f_{{ $n }}" type="number" step="{{ $f['step'] ?? 1 }}" name="{{ $n }}" value="{{ $val }}" class="field">@break
                        @case('password')<input id="f_{{ $n }}" type="password" name="{{ $n }}" class="field" autocomplete="new-password">@break
                        @default<input id="f_{{ $n }}" type="{{ $t === 'email' ? 'email' : 'text' }}" name="{{ $n }}" value="{{ $val }}" class="field">
                    @endswitch
                    @if(!empty($f['help']))<p class="mt-1 text-xs text-muted">{{ $f['help'] }}</p>@endif
                </div>
            @endif
        @endforeach
    </div>
    @if($seo) @include('admin.partials.seo-fields', ['model' => $m]) @endif
    <div class="flex items-center gap-3"><button class="btn btn-primary">Save</button><a href="{{ route($route . '.index') }}" class="btn btn-ghost">Cancel</a></div>
</form>
@endsection
