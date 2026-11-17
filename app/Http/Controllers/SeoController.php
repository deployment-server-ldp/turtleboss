<?php
namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class SeoController extends Controller
{
    public function sitemap()
    {
        $xml = Cache::remember('sitemap.xml', 3600, function () {
            $urls = [[url('/'), now(), 'daily', '1.0'], [route('shop'), now(), 'daily', '0.9'], [route('blog'), now(), 'weekly', '0.6']];
            foreach (Category::active()->with('seo')->get() as $c) if (!$c->seo?->noindex) $urls[] = [$c->url, $c->updated_at, 'weekly', '0.8'];
            foreach (Product::active()->with('seo')->get() as $p) if (!$p->seo?->noindex) $urls[] = [$p->url, $p->updated_at, 'weekly', '0.7'];
            foreach (Page::where('is_published', true)->with('seo')->get() as $pg) if (!$pg->seo?->noindex) $urls[] = [url($pg->slug), $pg->updated_at, 'monthly', '0.5'];
            foreach (BlogPost::live()->with('seo')->get() as $b) if (!$b->seo?->noindex) $urls[] = [route('blog.show', $b->slug), $b->updated_at, 'monthly', '0.5'];
            $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            foreach ($urls as [$loc, $mod, $freq, $prio]) {
                $out .= '<url><loc>' . e($loc) . '</loc><lastmod>' . $mod->toAtomString() . "</lastmod><changefreq>$freq</changefreq><priority>$prio</priority></url>\n";
            }
            return $out . '</urlset>';
        });
        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function robots()
    {
        $txt = rtrim(setting('robots_txt', "User-agent: *\nDisallow: /admin\n")) . "\n\nSitemap: " . url('/sitemap.xml') . "\n";
        return response($txt, 200, ['Content-Type' => 'text/plain']);
    }
}
