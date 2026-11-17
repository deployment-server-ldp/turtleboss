@extends('admin.layouts.admin')
@section('title', $title)
@section('content')
<div class="mb-4 flex flex-wrap items-center gap-3">
    <form class="flex gap-2" method="get"><input name="q" value="{{ request('q') }}" placeholder="Search…" class="field !w-60 !py-2"><button class="btn btn-outline btn-sm">Search</button></form>
    @if($canCreate)<a href="{{ route($route . '.create') }}" class="btn btn-primary btn-sm ml-auto">+ Add new</a>@endif
</div>
<div class="card overflow-x-auto">
    <table class="w-full min-w-[640px] text-left">
        <thead class="border-b border-line bg-soft text-xs uppercase text-muted"><tr>@foreach($columns as $label => $c)<th class="px-4 py-3">{{ $label }}</th>@endforeach<th class="px-4 py-3 text-right">Actions</th></tr></thead>
        <tbody class="divide-y divide-line">
            @forelse($rows as $row)
                <tr class="hover:bg-soft/60">
                    @foreach($columns as $label => $c)
                        @php $v = is_callable($c) ? $c($row) : data_get($row, $c); @endphp
                        <td class="px-4 py-3">@if(is_array($v) && ($v[0] ?? '') === 'img')<img src="{{ $v[1] }}" class="h-12 w-20 rounded object-cover" alt="">@elseif(in_array($v, ['Enabled', 'Active', 'Published']))<span class="badge bg-green-50 text-green-700">{{ $v }}</span>@elseif(in_array($v, ['Disabled', 'Draft', 'Inactive', 'Off']))<span class="badge bg-gray-100 text-gray-600">{{ $v }}</span>@else{{ $v }}@endif</td>
                    @endforeach
                    <td class="whitespace-nowrap px-4 py-3 text-right">
                        <a href="{{ route($route . '.edit', $row->getKey()) }}" class="font-medium text-brand hover:underline">Edit</a>
                        <form method="post" action="{{ route($route . '.destroy', $row->getKey()) }}" class="ml-3 inline" onsubmit="return confirm('Delete this item? This cannot be undone.')">@csrf @method('DELETE')<button class="text-red-600 hover:underline">Delete</button></form>
                    </td>
                </tr>
            @empty<tr><td colspan="{{ count($columns) + 1 }}" class="px-4 py-12 text-center text-muted">Nothing here yet.</td></tr>@endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $rows->links() }}</div>
@endsection
