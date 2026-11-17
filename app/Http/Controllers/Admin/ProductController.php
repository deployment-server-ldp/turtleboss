<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\ImageService;
use App\Services\InventoryService;
use App\Support\Html;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct(private ImageService $images, private InventoryService $inventory) {}

    public function index(Request $r)
    {
        $q = Product::query()->with(['images', 'category', 'variants']);
        if ($t = trim((string) $r->q)) $q->search($t);
        if ($r->filled('category')) $q->whereHas('categories', fn ($c) => $c->where('categories.id', $r->category));
        if ($r->status === 'active') $q->where('is_active', true);
        if ($r->status === 'draft') $q->where('is_active', false);
        if ($r->stock === 'low') $q->where('manage_stock', true)->whereColumn('stock_qty', '<=', 'low_stock_threshold')->where('stock_qty', '>', 0);
        if ($r->stock === 'out') $q->where(fn ($w) => $w->where('stock_status', 'out_of_stock')->orWhere(fn ($x) => $x->where('manage_stock', true)->where('stock_qty', '<=', 0)));
        if ($r->boolean('demo')) $q->where('is_demo', true);
        $sort = in_array($r->sort, ['name', 'price', 'stock_qty', 'created_at']) ? $r->sort : 'created_at';
        $products = $q->orderBy($sort, $r->sort ? 'asc' : 'desc')->paginate(20)->withQueryString();
        return view('admin.products.index', ['products' => $products, 'categories' => Category::orderBy('name')->get()]);
    }

    public function create() { return $this->form(new Product(['is_active' => true, 'manage_stock' => true, 'stock_status' => 'in_stock', 'low_stock_threshold' => (int) setting('low_stock_default', 5)])); }
    public function edit(Product $product) { return $this->form($product); }

    private function form(Product $p)
    {
        $p->load(['allImages', 'variants.values', 'variants.images', 'specs', 'categories', 'seo', 'relations_']);
        $attrs = Attribute::with('values')->orderBy('sort_order')->get();
        $variantRows = $p->variants->map(fn ($v) => [
            'id' => $v->id, 'sku' => $v->sku, 'price' => $v->price, 'sale_price' => $v->sale_price, 'stock_qty' => $v->stock_qty, 'is_active' => $v->is_active,
            'values' => $v->values->pluck('id')->all(), 'label' => $v->values->map->value->implode(' / '), 'image' => $v->images->first()?->url,
        ])->values();
        $others = Product::where('id', '!=', $p->id ?? 0)->orderBy('name')->pluck('name', 'id');
        $rel = fn ($t) => $p->relations_->where('type', $t)->pluck('related_id')->all();
        return view('admin.products.form', [
            'p' => $p, 'categories' => Category::orderBy('sort_order')->get(), 'attrs' => $attrs, 'variantRows' => $variantRows, 'others' => $others,
            'related' => $rel('related'), 'cross' => $rel('cross_sell'), 'upsell' => $rel('upsell'), 'logs' => $p->exists ? \App\Models\InventoryLog::with('user', 'variant.values')->where('product_id', $p->id)->latest()->limit(15)->get() : collect(),
        ]);
    }

    public function store(Request $r) { return $this->save($r, new Product); }
    public function update(Request $r, Product $product) { return $this->save($r, $product); }

    private function save(Request $r, Product $p)
    {
        $d = $r->validate([
            'name' => 'required|max:200', 'slug' => 'nullable|max:220', 'sku' => ['nullable', 'max:80', Rule::unique('products', 'sku')->ignore($p->id)], 'barcode' => 'nullable|max:80', 'brand' => 'nullable|max:100',
            'category_ids' => 'nullable|array', 'category_ids.*' => 'exists:categories,id', 'primary_category' => 'nullable|exists:categories,id',
            'short_description' => 'nullable|max:1000', 'description' => 'nullable',
            'price' => 'required|numeric|min:0|max:99999999', 'sale_price' => 'nullable|numeric|min:0|lt:price', 'cost_price' => 'nullable|numeric|min:0',
            'stock_qty' => 'nullable|integer|min:0|max:9999999', 'low_stock_threshold' => 'nullable|integer|min:0', 'stock_status' => 'required|in:in_stock,out_of_stock,backorder',
            'weight' => 'nullable|numeric|min:0', 'length' => 'nullable|numeric|min:0', 'width' => 'nullable|numeric|min:0', 'height' => 'nullable|numeric|min:0',
            'shipping_info' => 'nullable|max:2000', 'video_url' => 'nullable|url|max:255',
            'new_images.*' => 'image|mimes:jpg,jpeg,png,webp|max:6144', 'replace_image.*' => 'image|mimes:jpg,jpeg,png,webp|max:6144',
            'variants' => 'nullable|array', 'variants.*.sku' => 'nullable|max:80', 'variants.*.price' => 'nullable|numeric|min:0', 'variants.*.sale_price' => 'nullable|numeric|min:0',
            'variants.*.stock_qty' => 'nullable|integer|min:0', 'variants.*.values' => 'required_with:variants|array|min:1', 'variants.*.image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:6144',
            'specs.name.*' => 'nullable|max:100', 'specs.value.*' => 'nullable|max:255',
            'seo.title' => 'nullable|max:160', 'seo.description' => 'nullable|max:320', 'seo.focus_keyword' => 'nullable|max:100', 'seo.canonical_url' => 'nullable|url|max:255',
            'seo.og_title' => 'nullable|max:160', 'seo.og_description' => 'nullable|max:320', 'seo.og_image_file' => 'nullable|image|max:4096',
        ]);
        // duplicate variant SKUs / duplicate combos
        $skus = collect($d['variants'] ?? [])->pluck('sku')->filter();
        if ($skus->count() !== $skus->unique()->count()) return back()->withInput()->withErrors(['variants' => 'Variant SKUs must be unique.']);
        foreach ($d['variants'] ?? [] as $v) {
            if (!empty($v['sku']) && ProductVariant::where('sku', $v['sku'])->where('id', '!=', $v['id'] ?? 0)->exists()) return back()->withInput()->withErrors(['variants' => "Variant SKU {$v['sku']} is already in use."]);
        }
        $combos = collect($d['variants'] ?? [])->map(fn ($v) => collect($v['values'])->sort()->implode('-'));
        if ($combos->count() !== $combos->unique()->count()) return back()->withInput()->withErrors(['variants' => 'Two variants have the same option combination.']);

        $slugBase = Str::slug(($d['slug'] ?? null) ?: $d['name']) ?: 'product';
        $slug = $slugBase; $i = 2;
        while (Product::withTrashed()->where('slug', $slug)->where('id', '!=', $p->id ?? 0)->exists()) $slug = $slugBase . '-' . $i++;

        $oldQty = $p->exists ? $p->stock_qty : 0;
        $catIds = collect($d['category_ids'] ?? [])->map(fn ($x) => (int) $x);
        $primary = $d['primary_category'] ?? $catIds->first();
        $hasVariants = !empty($d['variants']);
        $manage = $r->boolean('manage_stock');
        $status = $d['stock_status'];
        $qty = (int) ($d['stock_qty'] ?? 0);
        if ($manage && !$hasVariants && $status !== 'backorder') $status = $qty > 0 ? 'in_stock' : 'out_of_stock';

        DB::transaction(function () use ($r, $p, $d, $slug, $catIds, $primary, $manage, $status, $qty, $oldQty, $hasVariants) {
            $p->fill([
                'name' => $d['name'], 'slug' => $slug, 'sku' => ($d['sku'] ?? null) ?: null, 'barcode' => $d['barcode'] ?? null, 'brand' => $d['brand'] ?? null,
                'category_id' => $primary, 'short_description' => $d['short_description'] ?? null, 'description' => Html::clean($d['description'] ?? null),
                'price' => $d['price'], 'sale_price' => $d['sale_price'] ?? null, 'cost_price' => $d['cost_price'] ?? null,
                'manage_stock' => $manage, 'stock_qty' => $qty, 'low_stock_threshold' => $d['low_stock_threshold'] ?? 5, 'stock_status' => $status,
                'weight' => $d['weight'] ?? null, 'length' => $d['length'] ?? null, 'width' => $d['width'] ?? null, 'height' => $d['height'] ?? null,
                'shipping_info' => $d['shipping_info'] ?? null, 'video_url' => $d['video_url'] ?? null,
                'is_active' => $r->boolean('is_active'), 'is_featured' => $r->boolean('is_featured'), 'is_bestseller' => $r->boolean('is_bestseller'),
                'is_new' => $r->boolean('is_new'), 'is_trending' => $r->boolean('is_trending'),
            ])->save();
            $p->categories()->sync($catIds->when($primary, fn ($c) => $c->push((int) $primary))->unique()->all());
            if ($qty !== $oldQty && !$hasVariants) \App\Models\InventoryLog::create(['product_id' => $p->id, 'change' => $qty - $oldQty, 'qty_after' => $qty, 'reason' => 'manual', 'note' => $p->wasRecentlyCreated ? 'Initial stock' : 'Edited in product form', 'user_id' => auth()->id()]);

            $this->saveImages($r, $p);
            $this->saveVariants($r, $p, $d['variants'] ?? []);

            $p->specs()->delete();
            foreach (($d['specs']['name'] ?? []) as $i => $n) {
                $val = $d['specs']['value'][$i] ?? null;
                if (trim((string) $n) !== '' && trim((string) $val) !== '') $p->specs()->create(['name' => $n, 'value' => $val, 'sort_order' => $i]);
            }
            $p->relations_()->delete();
            foreach (['related' => 'related_ids', 'cross_sell' => 'cross_ids', 'upsell' => 'upsell_ids'] as $type => $key) {
                foreach (array_unique((array) $r->input($key, [])) as $rid) if ((int) $rid !== $p->id) $p->relations_()->create(['related_id' => (int) $rid, 'type' => $type]);
            }
            $seo = $r->input('seo', []);
            $seo['og_image'] = $r->hasFile('seo.og_image_file') ? $this->images->store($r->file('seo.og_image_file'), 'seo') : $p->seo?->og_image;
            $p->saveSeo($seo);
        });
        Cache::forget('sitemap.xml');
        return redirect()->route('admin.products.edit', $p)->with('status', 'Product saved.');
    }

    private function saveImages(Request $r, Product $p): void
    {
        foreach ((array) $r->input('delete_images', []) as $id) {
            $img = $p->allImages()->whereNull('variant_id')->find($id);
            if ($img) { $this->images->delete($img->path); $img->delete(); }
        }
        foreach ($p->allImages()->whereNull('variant_id')->get() as $img) {
            $img->sort_order = (int) $r->input("image_order.{$img->id}", $img->sort_order);
            $img->alt = $r->input("image_alt.{$img->id}", $img->alt);
            if ($r->hasFile("replace_image.{$img->id}")) { $this->images->delete($img->path); $img->path = $this->images->store($r->file("replace_image.{$img->id}"), 'products', null, $img->alt); }
            $img->save();
        }
        $next = (int) $p->allImages()->max('sort_order') + 1;
        foreach ((array) $r->file('new_images', []) as $f) {
            $p->allImages()->create(['path' => $this->images->store($f, 'products', $p->name, $p->name), 'alt' => $p->name, 'sort_order' => $next++]);
        }
        // normalise order so the first image is the primary one
        foreach ($p->allImages()->whereNull('variant_id')->orderBy('sort_order')->orderBy('id')->get() as $n => $img) $img->update(['sort_order' => $n]);
    }

    private function saveVariants(Request $r, Product $p, array $rows): void
    {
        $keep = [];
        foreach (array_values($rows) as $n => $row) {
            $v = !empty($row['id']) ? $p->variants()->find($row['id']) : null;
            $v ??= new ProductVariant(['product_id' => $p->id]);
            $v->fill([
                'sku' => $row['sku'] ?: null, 'price' => ($row['price'] ?? '') === '' ? null : $row['price'], 'sale_price' => ($row['sale_price'] ?? '') === '' ? null : $row['sale_price'],
                'stock_qty' => (int) ($row['stock_qty'] ?? 0), 'is_active' => !empty($row['is_active']), 'sort_order' => $n,
            ]);
            $isNew = !$v->exists; $oldQty = $v->stock_qty;
            if ($v->isDirty('stock_qty') || $isNew) $change = $v->stock_qty - ($isNew ? 0 : $v->getOriginal('stock_qty'));
            $v->save();
            if (!empty($change)) \App\Models\InventoryLog::create(['product_id' => $p->id, 'variant_id' => $v->id, 'change' => $change, 'qty_after' => $v->stock_qty, 'reason' => 'manual', 'note' => $isNew ? 'Initial stock' : 'Edited in product form', 'user_id' => auth()->id()]);
            unset($change);
            $v->values()->sync($row['values']);
            if ($r->hasFile("variants.$n.image")) {
                foreach ($v->images as $old) { $this->images->delete($old->path); $old->delete(); }
                ProductImage::create(['product_id' => $p->id, 'variant_id' => $v->id, 'path' => $this->images->store($r->file("variants.$n.image"), 'products', null, $p->name), 'alt' => $p->name]);
            } elseif (!empty($row['remove_image'])) { foreach ($v->images as $old) { $this->images->delete($old->path); $old->delete(); } }
            $keep[] = $v->id;
        }
        $p->variants()->whereNotIn('id', $keep)->get()->each(function ($v) { foreach ($v->images as $i) { $this->images->delete($i->path); } $v->delete(); });
    }

    public function destroy(Product $product)
    {
        foreach ($product->allImages as $i) $this->images->delete($i->path);
        $product->delete();
        Cache::forget('sitemap.xml');
        return redirect()->route('admin.products.index')->with('status', 'Product deleted.');
    }

    public function duplicate(Product $product)
    {
        $product->load(['allImages', 'variants.values', 'specs', 'categories', 'relations_']);
        $copy = DB::transaction(function () use ($product) {
            $c = $product->replicate(['rating_avg', 'rating_count', 'sales_count', 'views']);
            $c->name = $product->name . ' (Copy)';
            $c->slug = Str::slug($c->name) . '-' . Str::lower(Str::random(4));
            $c->sku = null; $c->is_active = false;
            $c->save();
            $c->categories()->sync($product->categories->pluck('id'));
            foreach ($product->specs as $s) $c->specs()->create(['name' => $s->name, 'value' => $s->value, 'sort_order' => $s->sort_order]);
            foreach ($product->relations_ as $rel) $c->relations_()->create(['related_id' => $rel->related_id, 'type' => $rel->type]);
            // files are copied so deleting one product's images never breaks the other
            foreach ($product->allImages->whereNull('variant_id') as $img) {
                $new = 'products/' . date('Y/m') . '/' . bin2hex(random_bytes(8)) . '.' . pathinfo($img->path, PATHINFO_EXTENSION);
                if (\Storage::disk('public')->exists($img->path)) \Storage::disk('public')->copy($img->path, $new); else $new = $img->path;
                $c->allImages()->create(['path' => $new, 'alt' => $img->alt, 'sort_order' => $img->sort_order]);
            }
            foreach ($product->variants as $v) {
                $nv = $c->variants()->create(['sku' => null, 'price' => $v->price, 'sale_price' => $v->sale_price, 'stock_qty' => $v->stock_qty, 'is_active' => $v->is_active, 'sort_order' => $v->sort_order]);
                $nv->values()->sync($v->values->pluck('id'));
            }
            return $c;
        });
        return redirect()->route('admin.products.edit', $copy)->with('status', 'Product duplicated as a draft. Set a new SKU and review before publishing.');
    }

    public function toggle(Request $r, Product $product)
    {
        $f = $r->validate(['field' => 'required|in:is_active,is_featured,is_bestseller,is_new,is_trending'])['field'];
        $product->update([$f => !$product->$f]);
        return back()->with('status', 'Updated.');
    }

    /** Bulk operations from the product list. */
    public function bulk(Request $r)
    {
        $d = $r->validate(['action' => 'required|string', 'ids' => 'required|array|min:1', 'ids.*' => 'integer', 'value' => 'nullable', 'mode' => 'nullable|in:percent,fixed,set', 'category_id' => 'nullable|exists:categories,id']);
        $q = Product::whereIn('id', $d['ids']);
        $n = $q->count();
        switch ($d['action']) {
            case 'delete': $q->get()->each(fn ($p) => $p->delete()); break;
            case 'publish': $q->update(['is_active' => true]); break;
            case 'unpublish': $q->update(['is_active' => false]); break;
            case 'feature': $q->update(['is_featured' => true]); break;
            case 'unfeature': $q->update(['is_featured' => false]); break;
            case 'bestseller': $q->update(['is_bestseller' => true]); break;
            case 'new': $q->update(['is_new' => true]); break;
            case 'category':
                if (empty($d['category_id'])) return back()->withErrors(['bulk' => 'Choose a category.']);
                $q->get()->each(fn ($p) => $p->categories()->syncWithoutDetaching([$d['category_id']]));
                break;
            case 'price':
                $val = (float) ($d['value'] ?? 0); $mode = $d['mode'] ?? 'percent';
                if ($mode !== 'set' && $val == 0) return back()->withErrors(['bulk' => 'Enter a non-zero value.']);
                foreach ($q->get() as $p) {
                    $new = match ($mode) { 'percent' => $p->price * (1 + $val / 100), 'fixed' => $p->price + $val, default => $val };
                    $p->update(['price' => max(0, round($new, 2)), 'sale_price' => ($p->sale_price && $p->sale_price >= max(0, $new)) ? null : $p->sale_price]);
                }
                break;
            default: return back()->withErrors(['bulk' => 'Unknown action.']);
        }
        Cache::forget('sitemap.xml');
        return back()->with('status', "Bulk action applied to $n product(s).");
    }

    private const CSV_COLS = ['id', 'sku', 'name', 'slug', 'category', 'brand', 'barcode', 'short_description', 'description', 'price', 'sale_price', 'cost_price', 'stock_qty', 'stock_status', 'weight', 'length', 'width', 'height', 'is_active', 'is_featured', 'is_bestseller', 'is_new'];

    public function export()
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::CSV_COLS);
            Product::with('category')->orderBy('id')->chunk(200, function ($chunk) use ($out) {
                foreach ($chunk as $p) {
                    $row = [];
                    foreach (self::CSV_COLS as $c) $row[] = $c === 'category' ? $p->category?->name : (is_bool($p->$c) ? (int) $p->$c : self::csvSafe($p->$c));
                    fputcsv($out, $row);
                }
            });
            fclose($out);
        }, 'turtleboss-products-' . date('Ymd') . '.csv', ['Content-Type' => 'text/csv']);
    }
    /** Neutralise spreadsheet formula injection. */
    private static function csvSafe($v) { return is_string($v) && $v !== '' && in_array($v[0], ['=', '+', '-', '@'], true) ? "'" . $v : $v; }

    public function sampleCsv()
    {
        return response()->streamDownload(function () {
            $o = fopen('php://output', 'w'); fputcsv($o, self::CSV_COLS);
            fputcsv($o, ['', 'SKU-001', 'Example Product', '', 'Kitchen Accessories', 'Turtle Boss', '', 'Short text', 'Long description', 1500, 1200, 800, 25, 'in_stock', 0.5, '', '', '', 1, 0, 0, 1]); fclose($o);
        }, 'turtleboss-product-import-sample.csv', ['Content-Type' => 'text/csv']);
    }

    public function import(Request $r)
    {
        $r->validate(['file' => 'required|file|mimes:csv,txt|max:5120']);
        $h = fopen($r->file('file')->getRealPath(), 'r');
        $header = array_map(fn ($x) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', $x))), (array) fgetcsv($h));
        if (!in_array('name', $header) || !in_array('price', $header)) return back()->withErrors(['import' => 'CSV must contain at least the "name" and "price" columns.']);
        $created = $updated = 0; $errors = [];
        $line = 1;
        while (($row = fgetcsv($h)) !== false) {
            $line++;
            if (count($row) < 2) continue;
            $row = array_merge(array_fill_keys(self::CSV_COLS, null), array_combine($header, array_pad(array_slice($row, 0, count($header)), count($header), null)));
            $row = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $row);
            if (($row['name'] ?? '') === '' || !is_numeric($row['price'] ?? null)) { $errors[] = "Line $line: name and numeric price are required."; continue; }
            $p = null;
            if (!empty($row['id'])) $p = Product::find($row['id']);
            if (!$p && !empty($row['sku'])) $p = Product::where('sku', $row['sku'])->first();
            $isNew = !$p; $p ??= new Product;
            $cat = !empty($row['category']) ? Category::firstOrCreate(['slug' => Str::slug($row['category'])], ['name' => $row['category']]) : null;
            $slugBase = Str::slug($row['slug'] ?: $row['name']); $slug = $slugBase; $i = 2;
            if ($isNew || ($row['slug'] ?? '') !== '') { while (Product::withTrashed()->where('slug', $slug)->where('id', '!=', $p->id ?? 0)->exists()) $slug = $slugBase . '-' . $i++; } else $slug = $p->slug;
            $qty = (int) ($row['stock_qty'] ?? 0);
            $sale = is_numeric($row['sale_price'] ?? null) && (float) $row['sale_price'] < (float) $row['price'] ? $row['sale_price'] : null;
            $p->fill(array_filter([
                'name' => $row['name'], 'slug' => $slug, 'sku' => $row['sku'] ?: null, 'brand' => $row['brand'] ?? null, 'barcode' => $row['barcode'] ?? null,
                'short_description' => $row['short_description'] ?? null, 'description' => Html::clean($row['description'] ?? null),
                'cost_price' => is_numeric($row['cost_price'] ?? null) ? $row['cost_price'] : null,
                'weight' => is_numeric($row['weight'] ?? null) ? $row['weight'] : null, 'length' => is_numeric($row['length'] ?? null) ? $row['length'] : null,
                'width' => is_numeric($row['width'] ?? null) ? $row['width'] : null, 'height' => is_numeric($row['height'] ?? null) ? $row['height'] : null,
            ], fn ($v) => $v !== null && $v !== ''));
            $oldQty = $p->stock_qty ?? 0;
            $p->price = $row['price']; $p->sale_price = $sale; $p->stock_qty = $qty;
            $p->stock_status = in_array($row['stock_status'] ?? '', ['in_stock', 'out_of_stock', 'backorder']) ? $row['stock_status'] : ($qty > 0 ? 'in_stock' : 'out_of_stock');
            $p->is_active = !isset($row['is_active']) || $row['is_active'] === '' ? ($p->is_active ?? true) : (bool) (int) $row['is_active'];
            foreach (['is_featured', 'is_bestseller', 'is_new'] as $f) if (isset($row[$f]) && $row[$f] !== '') $p->$f = (bool) (int) $row[$f];
            if ($cat) $p->category_id = $cat->id;
            $p->save();
            if ($cat) $p->categories()->syncWithoutDetaching([$cat->id]);
            if ($qty !== $oldQty) \App\Models\InventoryLog::create(['product_id' => $p->id, 'change' => $qty - $oldQty, 'qty_after' => $qty, 'reason' => 'import', 'user_id' => auth()->id()]);
            $isNew ? $created++ : $updated++;
        }
        fclose($h);
        Cache::forget('sitemap.xml');
        return back()->with('status', "Import finished: $created created, $updated updated." . ($errors ? ' ' . count($errors) . ' row(s) skipped.' : ''))->with('import_errors', array_slice($errors, 0, 20));
    }
}
