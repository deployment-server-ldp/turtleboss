<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class SeoOverviewController extends Controller
{
    public function index()
    {
        $missing = fn ($model) => $model::whereDoesntHave('seo', fn ($s) => $s->whereNotNull('description')->where('description', '!=', ''))->count();
        return view('admin.seo', [
            'missing' => ['Products' => $missing(Product::class), 'Categories' => $missing(Category::class), 'Pages' => $missing(Page::class)],
            'noindex' => Product::whereHas('seo', fn ($s) => $s->where('noindex', true))->count(),
            'redirects' => \App\Models\Redirect::count(),
            'noAlt' => \App\Models\ProductImage::whereNull('alt')->orWhere('alt', '')->count(),
        ]);
    }
    public function regenerate() { Cache::forget('sitemap.xml'); return back()->with('status', 'Sitemap cache cleared; it will be regenerated on the next request.'); }
}
