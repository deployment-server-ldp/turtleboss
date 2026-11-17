<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MenuController extends Controller
{
    public function index() { return view('admin.menus.index', ['menus' => Menu::withCount('items')->orderBy('id')->get()]); }

    public function store(Request $r)
    {
        $d = $r->validate(['name' => 'required|max:80', 'location' => 'required|alpha_dash|max:50|unique:menus,location']);
        $m = Menu::create($d);
        return redirect()->route('admin.menus.edit', $m)->with('status', 'Menu created.');
    }

    public function edit(Menu $menu)
    {
        $menu->load(['items' => fn ($q) => $q->orderBy('sort_order')]);
        $flat = [];
        $walk = function ($parent, $depth) use (&$walk, &$flat, $menu) {
            foreach ($menu->items->where('parent_id', $parent) as $i) { $flat[] = ['item' => $i, 'depth' => $depth]; $walk($i->id, $depth + 1); }
        };
        $walk(null, 0);
        return view('admin.menus.edit', ['menu' => $menu, 'flat' => $flat, 'categories' => Category::orderBy('name')->get(), 'pages' => Page::orderBy('title')->get()]);
    }

    private function flush(Menu $menu) { Cache::forget("menu.{$menu->location}"); }

    public function addItem(Request $r, Menu $menu)
    {
        $d = $r->validate(['label' => 'required|max:100', 'url' => 'required|max:500', 'parent_id' => 'nullable|exists:menu_items,id', 'type' => 'nullable|in:link,mega_categories', 'open_new_tab' => 'nullable|boolean']);
        if (!empty($d['parent_id']) && !$menu->items()->whereKey($d['parent_id'])->exists()) abort(422);
        if (!preg_match('#^(https?://|/|\#|mailto:|tel:)#i', $d['url'])) $d['url'] = '/' . $d['url'];
        $menu->items()->create($d + ['sort_order' => (int) $menu->items()->max('sort_order') + 1, 'type' => $d['type'] ?? 'link', 'open_new_tab' => $r->boolean('open_new_tab')]);
        $this->flush($menu);
        return back()->with('status', 'Menu item added.');
    }

    public function updateItem(Request $r, MenuItem $item)
    {
        $d = $r->validate(['label' => 'required|max:100', 'url' => 'required|max:500', 'parent_id' => 'nullable|exists:menu_items,id', 'type' => 'nullable|in:link,mega_categories', 'sort_order' => 'nullable|integer']);
        if (!empty($d['parent_id'])) {
            $p = MenuItem::find($d['parent_id']);
            // prevent cycles / cross-menu parents
            $a = $p; while ($a) { if ($a->id === $item->id) abort(422, 'Invalid parent.'); $a = $a->parent; }
            if ($p->menu_id !== $item->menu_id) abort(422);
        }
        $item->update($d + ['is_active' => $r->boolean('is_active'), 'open_new_tab' => $r->boolean('open_new_tab'), 'type' => $d['type'] ?? 'link']);
        return back()->with('status', 'Menu item updated.');
    }

    public function deleteItem(MenuItem $item) { $menu = $item->menu; $item->delete(); $this->flush($menu); return back()->with('status', 'Menu item removed.'); }

    public function move(Request $r, MenuItem $item)
    {
        $dir = $r->input('dir') === 'up' ? -1 : 1;
        $sibs = MenuItem::where('menu_id', $item->menu_id)->where('parent_id', $item->parent_id)->orderBy('sort_order')->orderBy('id')->get()->values();
        $i = $sibs->search(fn ($x) => $x->id === $item->id);
        $j = $i + $dir;
        if (isset($sibs[$j])) { $arr = $sibs->all(); [$arr[$i], $arr[$j]] = [$arr[$j], $arr[$i]]; foreach ($arr as $n => $m) $m->update(['sort_order' => $n]); }
        $this->flush($item->menu);
        return back();
    }

    public function destroy(Menu $menu)
    {
        abort_if(in_array($menu->location, ['header', 'footer_help', 'footer_shop']), 422, 'Core menus cannot be deleted.');
        $menu->delete();
        return redirect()->route('admin.menus.index')->with('status', 'Menu deleted.');
    }
}
