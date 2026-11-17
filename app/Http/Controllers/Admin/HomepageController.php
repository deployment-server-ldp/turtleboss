<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Product;
use App\Services\ImageService;
use Illuminate\Http\Request;

class HomepageController extends Controller
{
    public const TYPES = [
        'categories' => 'Shop by Categories', 'products' => 'Product Collection', 'banners' => 'Promotional Banners',
        'why_choose' => 'Why Choose Us', 'testimonials' => 'Customer Reviews', 'newsletter' => 'Newsletter', 'rich_text' => 'Custom Text Block',
    ];

    public function index()
    {
        return view('admin.homepage.index', ['sections' => HomepageSection::orderBy('sort_order')->get(), 'types' => self::TYPES]);
    }

    public function edit(HomepageSection $section)
    {
        return view('admin.homepage.edit', ['s' => $section, 'types' => self::TYPES, 'products' => Product::orderBy('name')->pluck('name', 'id'), 'categories' => Category::orderBy('name')->pluck('name', 'id')]);
    }

    public function store(Request $r)
    {
        $d = $r->validate(['type' => 'required|in:' . implode(',', array_keys(self::TYPES))]);
        $s = HomepageSection::create(['type' => $d['type'], 'title' => self::TYPES[$d['type']], 'config' => $d['type'] === 'products' ? ['source' => 'new', 'limit' => 8] : [], 'sort_order' => (int) HomepageSection::max('sort_order') + 1, 'is_active' => false]);
        return redirect()->route('admin.homepage.edit', $s)->with('status', 'Section added (disabled until you enable it).');
    }

    public function update(Request $r, HomepageSection $section, ImageService $images)
    {
        $d = $r->validate(['title' => 'nullable|max:160', 'subtitle' => 'nullable|max:255', 'is_active' => 'nullable|boolean']);
        $cfg = $section->config ?? [];
        if ($section->type === 'products') {
            $cfg = ['source' => $r->validate(['source' => 'required|in:featured,bestseller,new,trending,category,manual'])['source'], 'limit' => min(24, max(2, (int) $r->input('limit', 8))),
                'category_id' => $r->input('category_id'), 'product_ids' => array_map('intval', (array) $r->input('product_ids', [])), 'cta_text' => $r->input('cta_text'), 'cta_url' => $r->input('cta_url')];
        } elseif ($section->type === 'testimonials') {
            $cfg['limit'] = min(12, max(1, (int) $r->input('limit', 6)));
        } elseif ($section->type === 'why_choose') {
            $cfg['items'] = collect($r->input('items', []))->filter(fn ($i) => !empty($i['title']))->map(fn ($i) => ['icon' => $i['icon'] ?? 'badge', 'title' => $i['title'], 'text' => $i['text'] ?? ''])->values()->all();
        } elseif ($section->type === 'rich_text') {
            $cfg['html'] = \App\Support\Html::clean($r->input('html'));
        } elseif ($section->type === 'banners') {
            $r->validate(['banners.*.image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:6144']);
            $items = [];
            foreach ((array) $r->input('banners', []) as $i => $b) {
                $existing = $b['existing_image'] ?? null;
                if ($r->hasFile("banners.$i.image")) { $images->delete($existing); $existing = $images->store($r->file("banners.$i.image"), 'banners'); }
                if (!empty($b['remove'])) { $images->delete($existing); continue; }
                if (!$existing && empty($b['heading'])) continue;
                $items[] = ['image' => $existing, 'heading' => $b['heading'] ?? '', 'text' => $b['text'] ?? '', 'cta_text' => $b['cta_text'] ?? '', 'cta_url' => $b['cta_url'] ?? '', 'alt' => $b['alt'] ?? ''];
            }
            $cfg['items'] = $items;
        }
        $section->update(['title' => $d['title'] ?? null, 'subtitle' => $d['subtitle'] ?? null, 'is_active' => $r->boolean('is_active'), 'config' => $cfg]);
        return redirect()->route('admin.homepage.edit', $section)->with('status', 'Section saved.');
    }

    public function reorder(Request $r)
    {
        foreach ((array) $r->input('order', []) as $i => $id) HomepageSection::whereKey($id)->update(['sort_order' => $i + 1]);
        return $r->expectsJson() ? response()->json(['ok' => true]) : back()->with('status', 'Order saved.');
    }
    public function toggle(HomepageSection $section) { $section->update(['is_active' => !$section->is_active]); return back(); }
    public function destroy(HomepageSection $section) { $section->delete(); return redirect()->route('admin.homepage.index')->with('status', 'Section removed.'); }
}
