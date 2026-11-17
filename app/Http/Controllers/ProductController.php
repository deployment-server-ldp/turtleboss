<?php
namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Services\ImageService;
use App\Support\Seo;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private function load(string $slug): Product
    {
        return Product::active()->where('slug', $slug)->with([
            'images', 'specs', 'category.parent', 'seo', 'variants.values.attribute', 'variants.images',
            'approvedReviews.user', 'relatedProducts.images', 'relatedProducts.variants',
        ])->firstOrFail();
    }

    /** Variant matrix + gallery for the product page script. */
    public static function config(Product $p): array
    {
        $attrs = []; $variants = [];
        foreach ($p->variants as $v) {
            $map = [];
            foreach ($v->values as $val) {
                $a = $val->attribute;
                $attrs[$a->id] ??= ['id' => $a->id, 'name' => $a->name, 'type' => $a->type, 'sort' => $a->sort_order, 'values' => []];
                $attrs[$a->id]['values'][$val->id] = ['id' => $val->id, 'value' => $val->value, 'hex' => $val->color_hex, 'sort' => $val->sort_order];
                $map[$a->id] = $val->id;
            }
            $variants[] = [
                'id' => $v->id, 'sku' => $v->sku, 'map' => $map, 'active' => $v->is_active,
                'regular' => $v->regular_price_for($p), 'price' => $v->final_price_for($p),
                'stock' => $p->stock_status === 'backorder' ? 9999 : ($p->manage_stock === false ? 9999 : $v->stock_qty),
                'images' => $v->images->map->url->values(),
            ];
        }
        $attrs = collect($attrs)->sortBy('sort')->map(fn ($a) => $a + [])->map(function ($a) { $a['values'] = collect($a['values'])->sortBy('sort')->values()->all(); return $a; })->values()->all();
        return [
            'productId' => $p->id, 'attributes' => $attrs, 'variants' => $variants,
            'base' => ['regular' => $p->regular_price, 'price' => $p->final_price, 'sku' => $p->sku, 'stock' => ($p->stock_status === 'out_of_stock') ? 0 : (($p->manage_stock && $p->stock_status !== 'backorder') ? $p->stock_qty : 9999)],
            'hasVariants' => count($variants) > 0,
            'images' => $p->images->map(fn ($i) => ['url' => $i->url, 'alt' => $i->alt ?: $p->name])->values(),
            'maxQty' => 20,
        ];
    }

    public function show(string $slug)
    {
        $p = $this->load($slug);
        $p->increment('views');

        $recent = collect(session('recent', []))->reject(fn ($id) => $id == $p->id)->take(8);
        $recentProducts = $recent->isEmpty() ? collect() : Product::active()->with('images', 'variants')->whereIn('id', $recent)->get()->sortBy(fn ($x) => $recent->search($x->id))->values();
        session(['recent' => $recent->prepend($p->id)->take(10)->values()->all()]);

        $related = $p->relatedProducts->where('is_active', true);
        if ($related->isEmpty() && $p->category_id) {
            $related = Product::active()->with('images', 'variants')->where('category_id', $p->category_id)->where('id', '!=', $p->id)->inRandomOrder()->limit(4)->get();
        }

        $images = $p->images->map->url->all() ?: [img(null)];
        $crumbs = [['name' => 'Home', 'url' => url('/')], ['name' => 'Shop', 'url' => route('shop')]];
        if ($p->category) {
            if ($p->category->parent) $crumbs[] = ['name' => $p->category->parent->name, 'url' => $p->category->parent->url];
            $crumbs[] = ['name' => $p->category->name, 'url' => $p->category->url];
        }
        $crumbs[] = ['name' => $p->name, 'url' => $p->url];

        $seo = Seo::fromModel($p, $p->name, $p->short_description ?: $p->description, $images[0], [
            'type' => 'product', 'canonical' => $p->seo?->canonical_url ?: $p->url,
            'schema' => [Seo::productSchema($p, $images), Seo::breadcrumbSchema($crumbs)],
        ]);

        // Only verified buyers who purchased and have not reviewed yet get verified status; anyone logged in can review.
        $canReview = auth()->check();
        $dist = $p->approvedReviews->groupBy('rating')->map->count();

        return view('product.show', [
            'p' => $p, 'config' => self::config($p), 'related' => $related, 'recent' => $recentProducts,
            'crumbs' => $crumbs, 'seo' => $seo, 'canReview' => $canReview, 'dist' => $dist,
            'upsells' => $p->relatedProducts('upsell')->with('images', 'variants')->get(),
        ]);
    }

    public function quick(string $slug)
    {
        $p = $this->load($slug);
        return response()->json(['html' => view('product._quick', ['p' => $p, 'config' => self::config($p)])->render(), 'config' => self::config($p)]);
    }

    public function review(Request $request, string $slug, ImageService $images)
    {
        $p = Product::active()->where('slug', $slug)->firstOrFail();
        $data = $request->validate([
            'rating' => 'required|integer|between:1,5', 'title' => 'nullable|string|max:120', 'body' => 'required|string|min:5|max:2000',
            'name' => auth()->check() ? 'nullable' : 'required|string|max:80', 'email' => auth()->check() ? 'nullable' : 'required|email|max:120',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
        ]);
        $user = auth()->user();
        $email = $user?->email ?? $data['email'];
        // Verified purchase: a non-cancelled order by this user/email containing this product.
        $verified = Order::whereNotIn('status', ['cancelled', 'refunded'])
            ->where(fn ($q) => $user ? $q->where('user_id', $user->id)->orWhere('customer_email', $email) : $q->where('customer_email', $email))
            ->whereHas('items', fn ($i) => $i->where('product_id', $p->id))->exists();
        $path = $request->hasFile('image') ? $images->store($request->file('image'), 'reviews', null, null, false) : null;
        Review::create([
            'product_id' => $p->id, 'user_id' => $user?->id, 'name' => $user?->name ?? $data['name'], 'email' => $email,
            'rating' => $data['rating'], 'title' => $data['title'] ?? null, 'body' => $data['body'], 'image' => $path,
            'is_verified' => $verified, 'is_approved' => false,
        ]);
        return back()->with('status', 'Thank you! Your review was submitted and will appear after approval.');
    }
}
