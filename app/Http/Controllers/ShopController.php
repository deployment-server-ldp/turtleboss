<?php
namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopController extends Controller
{
    public function index(Request $request) { return $this->listing($request, null); }

    public function category(Request $request, string $slug)
    {
        $category = Category::active()->where('slug', $slug)->with('seo')->firstOrFail();
        return $this->listing($request, $category);
    }

    private function listing(Request $request, ?Category $category)
    {
        $q = Product::active()->with(['images', 'variants']);

        if ($category) {
            $ids = $category->selfAndDescendantIds();
            $q->where(fn ($w) => $w->whereIn('products.category_id', $ids)->orWhereHas('categories', fn ($c) => $c->whereIn('categories.id', $ids)));
        } elseif ($request->filled('cat')) {
            $cat = Category::active()->where('slug', $request->cat)->first();
            if ($cat) { $ids = $cat->selfAndDescendantIds(); $q->where(fn ($w) => $w->whereIn('products.category_id', $ids)->orWhereHas('categories', fn ($c) => $c->whereIn('categories.id', $ids))); }
        }
        if ($term = trim((string) $request->q)) $q->search($term);

        $min = $request->filled('min') ? (float) $request->min : null;
        $max = $request->filled('max') ? (float) $request->max : null;
        if ($min !== null) $q->where('products.price', '>=', $min);
        if ($max !== null) $q->where('products.price', '<=', $max);

        if ($request->availability === 'in_stock') {
            $q->where('products.stock_status', '!=', 'out_of_stock')->where(fn ($w) => $w
                ->where('products.stock_status', 'backorder')
                ->orWhere('products.manage_stock', false)
                ->orWhere('products.stock_qty', '>', 0)
                ->orWhereHas('variants', fn ($v) => $v->where('is_active', true)->where('stock_qty', '>', 0)));
        }
        if ($request->filled('rating')) $q->where('products.rating_avg', '>=', (int) $request->rating);
        if ($request->filled('brand')) $q->whereIn('products.brand', (array) $request->brand);

        // attribute filters: attr[<attribute_id>][]=<value_id>
        foreach ((array) $request->input('attr', []) as $attrId => $valueIds) {
            $valueIds = array_filter((array) $valueIds, 'is_numeric');
            if (!$valueIds) continue;
            $q->whereHas('variants', fn ($v) => $v->where('is_active', true)->whereHas('values', fn ($x) => $x->whereIn('attribute_values.id', $valueIds)));
        }

        match ($request->input('sort', 'featured')) {
            'best' => $q->orderByDesc('sales_count'),
            'newest' => $q->latest(),
            'price_asc' => $q->orderBy('price'),
            'price_desc' => $q->orderByDesc('price'),
            'rating' => $q->orderByDesc('rating_avg')->orderByDesc('rating_count'),
            default => $q->orderByDesc('is_featured')->latest(),
        };

        $products = $q->paginate(12)->withQueryString();

        if ($request->ajax() && $request->boolean('more')) {
            return response()->json([
                'html' => view('shop._grid_items', compact('products'))->render(),
                'next' => $products->nextPageUrl(),
            ]);
        }

        $attributes = Attribute::where('is_filterable', true)->with('values')->orderBy('sort_order')->get()->map(function ($a) {
            $used = DB::table('variant_attribute_values as vav')->join('product_variants as pv', 'pv.id', '=', 'vav.variant_id')->where('pv.is_active', true)
                ->whereIn('vav.attribute_value_id', $a->values->pluck('id'))->pluck('vav.attribute_value_id')->unique();
            $a->setRelation('values', $a->values->whereIn('id', $used)->values());
            return $a;
        })->filter(fn ($a) => $a->values->isNotEmpty())->values();

        $brands = Product::active()->whereNotNull('brand')->where('brand', '!=', '')->distinct()->orderBy('brand')->pluck('brand');
        $priceMax = (int) ceil(Product::active()->max('price') ?? 0);
        $categories = Category::active()->roots()->with(['children' => fn ($c) => $c->where('is_active', true)])->orderBy('sort_order')->get();

        $crumbs = [['name' => 'Home', 'url' => url('/')], ['name' => 'Shop', 'url' => route('shop')]];
        if ($category) {
            if ($category->parent) $crumbs[] = ['name' => $category->parent->name, 'url' => $category->parent->url];
            $crumbs[] = ['name' => $category->name, 'url' => $category->url];
        }
        $filtered = $request->hasAny(['q', 'min', 'max', 'availability', 'rating', 'brand', 'attr', 'sort', 'cat']);
        $title = $category ? ($category->seo?->title ?: $category->name) : ($request->filled('q') ? 'Search: ' . $request->q : 'Shop');
        $seo = $category
            ? Seo::fromModel($category, $title, $category->description, $category->image ? img($category->image) : null, [
                'noindex' => ($category->seo?->noindex || ($filtered && $request->hasAny(['q', 'min', 'max', 'availability', 'rating', 'brand', 'attr']))) ? true : null,
                'prev' => $products->previousPageUrl(), 'next' => $products->nextPageUrl(),
                'schema' => [Seo::breadcrumbSchema($crumbs)],
            ])
            : Seo::make(['title' => $title, 'description' => 'Browse all Turtle Boss products.', 'noindex' => $request->hasAny(['q', 'min', 'max', 'availability', 'rating', 'brand', 'attr', 'sort', 'cat']),
                'prev' => $products->previousPageUrl(), 'next' => $products->nextPageUrl(), 'schema' => [Seo::breadcrumbSchema($crumbs)]]);

        return view('shop.index', compact('products', 'category', 'attributes', 'brands', 'priceMax', 'categories', 'crumbs', 'seo'));
    }

    /** Live search suggestions (product thumb, name, price). */
    public function suggest(Request $request)
    {
        $term = trim((string) $request->q);
        if (mb_strlen($term) < 2) return response()->json([]);
        $products = Product::active()->search($term)->with('images', 'variants')->orderByDesc('sales_count')->limit(6)->get();
        return response()->json($products->map(fn ($p) => [
            'name' => $p->name, 'url' => $p->url, 'image' => $p->image_url, 'price' => money($p->final_price),
            'regular' => $p->on_sale ? money($p->regular_price) : null,
        ]));
    }
}
