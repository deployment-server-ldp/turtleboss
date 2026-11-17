<?php
namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Product;
use App\Models\Review;
use App\Models\Slider;
use App\Support\Seo;

class HomeController extends Controller
{
    public function index()
    {
        $sections = HomepageSection::where('is_active', true)->orderBy('sort_order')->get()->map(function ($s) {
            $cfg = $s->config ?? [];
            $data = [];
            if ($s->type === 'categories') {
                $data['categories'] = Category::active()->roots()->orderBy('sort_order')->get();
            } elseif ($s->type === 'products') {
                $limit = (int) ($cfg['limit'] ?? 8);
                $q = Product::active()->with(['images', 'variants']);
                match ($cfg['source'] ?? 'featured') {
                    'featured' => $q->where('is_featured', true)->latest(),
                    'bestseller' => $q->where('is_bestseller', true)->orderByDesc('sales_count'),
                    'new' => $q->where('is_new', true)->latest(),
                    'trending' => $q->where('is_trending', true)->orderByDesc('views'),
                    'category' => $q->whereHas('categories', fn ($c) => $c->where('categories.id', $cfg['category_id'] ?? 0)),
                    'manual' => $q->whereIn('id', $cfg['product_ids'] ?? [])->orderByRaw('1'),
                    default => $q->latest(),
                };
                $data['products'] = $q->limit($limit)->get();
            } elseif ($s->type === 'testimonials') {
                $data['reviews'] = Review::with('product')->where('is_approved', true)->where('rating', '>=', 4)->latest()->limit((int) ($cfg['limit'] ?? 6))->get();
            }
            return ['section' => $s, 'data' => $data];
        })->filter(function ($x) {
            // Hide sections with nothing to show (no fake content).
            return match ($x['section']->type) {
                'products' => $x['data']['products']->isNotEmpty(),
                'categories' => $x['data']['categories']->isNotEmpty(),
                'testimonials' => $x['data']['reviews']->isNotEmpty(),
                'banners' => !empty($x['section']->config['items']),
                default => true,
            };
        });

        $seo = Seo::make(['title' => setting('seo_title', 'Turtle Boss'), 'append_site' => false, 'schema' => [
            ['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => 'Turtle Boss', 'url' => url('/'), 'logo' => file_exists(public_path('images/logo.png')) ? asset('images/logo.png') : null,
                'sameAs' => array_values(array_filter([setting('social_facebook'), setting('social_instagram'), setting('social_tiktok')]))],
            ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => 'Turtle Boss', 'url' => url('/'),
                'potentialAction' => ['@type' => 'SearchAction', 'target' => url('/shop') . '?q={search_term_string}', 'query-input' => 'required name=search_term_string']],
        ]]);

        return view('home', ['sliders' => Slider::live()->get(), 'sections' => $sections, 'seo' => $seo]);
    }
}
