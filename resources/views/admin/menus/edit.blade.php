@extends('admin.layouts.admin')
@section('title', 'Menu: ' . $menu->name)
@section('content')
<div class="grid gap-6 lg:grid-cols-[1fr_340px]">
    <div class="space-y-2">
        @forelse($flat as $row) @php $i = $row['item']; @endphp
            <details class="card" style="margin-left: {{ $row['depth'] * 24 }}px"><summary class="flex cursor-pointer items-center gap-3 p-3"><span class="font-medium {{ $i->is_active ? '' : 'text-gray-400 line-through' }}">{{ $i->label }}</span><span class="truncate text-xs text-muted">{{ $i->url }}</span>@if($i->type === 'mega_categories')<span class="badge bg-navy-50 text-navy">Mega menu</span>@endif
                <span class="ml-auto flex gap-1" onclick="event.stopPropagation()"><button form="mv{{ $i->id }}u" class="px-1.5 text-muted hover:text-navy">▲</button><button form="mv{{ $i->id }}d" class="px-1.5 text-muted hover:text-navy">▼</button></span></summary>
                <form method="post" action="{{ route('admin.menus.items.update', $i) }}" class="grid gap-2 border-t border-line p-3 sm:grid-cols-2">@csrf @method('PUT')
                    <input name="label" value="{{ $i->label }}" class="field !py-2" required><input name="url" value="{{ $i->url }}" class="field !py-2" required>
                    <select name="parent_id" class="field !py-2"><option value="">Top level</option>@foreach($menu->items->where('id', '!=', $i->id) as $p)<option value="{{ $p->id }}" @selected($i->parent_id === $p->id)>Under: {{ $p->label }}</option>@endforeach</select>
                    <select name="type" class="field !py-2"><option value="link" @selected($i->type === 'link')>Normal link</option><option value="mega_categories" @selected($i->type === 'mega_categories')>Categories mega menu</option></select>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($i->is_active)> Enabled</label><label class="flex items-center gap-2 text-sm"><input type="checkbox" name="open_new_tab" value="1" @checked($i->open_new_tab)> Open in new tab</label>
                    <div class="flex gap-2 sm:col-span-2"><button class="btn btn-primary btn-sm">Save</button><button form="dl{{ $i->id }}" onclick="return confirm('Remove item and its sub-items?')" class="btn btn-outline btn-sm !text-red-600">Delete</button></div></form></details>
            <form id="mv{{ $i->id }}u" method="post" action="{{ route('admin.menus.items.move', $i) }}" class="hidden">@csrf<input type="hidden" name="dir" value="up"></form><form id="mv{{ $i->id }}d" method="post" action="{{ route('admin.menus.items.move', $i) }}" class="hidden">@csrf<input type="hidden" name="dir" value="down"></form><form id="dl{{ $i->id }}" method="post" action="{{ route('admin.menus.items.delete', $i) }}" class="hidden">@csrf @method('DELETE')</form>
        @empty<div class="card p-10 text-center text-muted">No items yet.</div>@endforelse
    </div>
    <aside class="space-y-4">
        <form method="post" action="{{ route('admin.menus.items.add', $menu) }}" class="card space-y-2 p-4" x-data="{ pick(e) { const [l, u] = e.target.value.split('|'); if (l) { this.$refs.l.value = l; this.$refs.u.value = u; } } }">@csrf<h2 class="text-base">Add menu item</h2>
            <select class="field !py-2" @change="pick($event)"><option value="">Quick pick (page / category)…</option><optgroup label="Pages">@foreach($pages as $p)<option value="{{ $p->title }}|/{{ $p->slug }}">{{ $p->title }}</option>@endforeach</optgroup><optgroup label="Categories">@foreach($categories as $c)<option value="{{ $c->name }}|/category/{{ $c->slug }}">{{ $c->name }}</option>@endforeach</optgroup><option value="Shop|/shop">Shop</option><option value="Blog|/blog">Blog</option></select>
            <input x-ref="l" name="label" class="field !py-2" placeholder="Label" required><input x-ref="u" name="url" class="field !py-2" placeholder="/shop or https://…" required>
            <select name="parent_id" class="field !py-2"><option value="">Top level</option>@foreach($menu->items as $p)<option value="{{ $p->id }}">Under: {{ $p->label }}</option>@endforeach</select>
            <select name="type" class="field !py-2"><option value="link">Normal link</option><option value="mega_categories">Categories mega menu</option></select>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="open_new_tab" value="1"> Open in new tab</label><button class="btn btn-primary w-full">Add item</button></form>
        @unless(in_array($menu->location, ['header', 'footer_help', 'footer_shop']))<form method="post" action="{{ route('admin.menus.destroy', $menu) }}" onsubmit="return confirm('Delete menu?')">@csrf @method('DELETE')<button class="btn btn-outline btn-sm w-full !text-red-600">Delete menu</button></form>@endunless
    </aside>
</div>
@endsection
