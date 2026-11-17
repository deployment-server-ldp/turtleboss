<?php
namespace App\Http\Controllers;

use App\Models\Page;
use App\Support\Seo;

class PageController extends Controller
{
    public function show(string $slug)
    {
        $page = Page::where('slug', $slug)->where('is_published', true)->with('seo')->firstOrFail();
        $crumbs = [['name' => 'Home', 'url' => url('/')], ['name' => $page->title, 'url' => url($page->slug)]];
        $seo = Seo::fromModel($page, $page->title, strip_tags(\Illuminate\Support\Str::limit($page->content, 160)), $page->banner ? img($page->banner) : null, ['schema' => [Seo::breadcrumbSchema($crumbs)]]);
        return view('page', compact('page', 'seo', 'crumbs'));
    }
}
