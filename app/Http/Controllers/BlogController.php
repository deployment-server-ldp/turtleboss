<?php
namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Support\Seo;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $r, ?string $kind = null, ?string $slug = null)
    {
        $q = BlogPost::live()->with('category', 'author')->latest('published_at');
        $heading = 'Blog';
        if ($kind === 'category') { $c = BlogCategory::where('slug', $slug)->firstOrFail(); $q->where('blog_category_id', $c->id); $heading = $c->name; }
        if ($kind === 'tag') { $t = BlogTag::where('slug', $slug)->firstOrFail(); $q->whereHas('tags', fn ($x) => $x->where('blog_tags.id', $t->id)); $heading = '#' . $t->name; }
        if ($term = trim((string) $r->q)) { $q->where(fn ($w) => $w->where('title', 'like', "%$term%")->orWhere('excerpt', 'like', "%$term%")); $heading = "Search: $term"; }
        $posts = $q->paginate(9)->withQueryString();
        return view('blog.index', [
            'posts' => $posts, 'heading' => $heading, 'categories' => BlogCategory::withCount(['posts' => fn ($p) => $p->live()])->get(),
            'seo' => Seo::make(['title' => $heading === 'Blog' ? 'Blog' : "$heading – Blog", 'description' => 'Tips, guides and news from Turtle Boss.', 'noindex' => $r->filled('q'), 'prev' => $posts->previousPageUrl(), 'next' => $posts->nextPageUrl()]),
        ]);
    }

    public function show(string $slug)
    {
        $post = BlogPost::live()->where('slug', $slug)->with('category', 'author', 'tags', 'seo')->firstOrFail();
        $related = BlogPost::live()->where('id', '!=', $post->id)->when($post->blog_category_id, fn ($q) => $q->where('blog_category_id', $post->blog_category_id))->latest('published_at')->limit(3)->get();
        $seo = Seo::fromModel($post, $post->title, $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($post->content), 160), $post->featured_image ? img($post->featured_image) : null, [
            'type' => 'article',
            'schema' => [['@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => $post->title, 'datePublished' => optional($post->published_at ?? $post->created_at)->toIso8601String(),
                'image' => $post->featured_image ? img($post->featured_image) : null, 'author' => ['@type' => 'Person', 'name' => $post->author?->name ?? 'Turtle Boss']]],
        ]);
        return view('blog.show', compact('post', 'related', 'seo'));
    }
}
