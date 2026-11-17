<?php
namespace App\Http\Controllers\Admin;

use App\Models as M;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogPostController extends ResourceController
{
    protected string $model = M\BlogPost::class; protected string $route = 'admin.blog'; protected string $title = 'Blog Posts'; protected string $singular = 'Post';
    protected bool $seo = true; protected array $searchable = ['title']; protected array $with = ['category', 'author']; protected string $images = 'blog';
    protected function fields(?Model $m): array
    {
        return [
            ['name' => 'title', 'label' => 'Title', 'rules' => 'required|max:200'],
            ['name' => 'slug', 'label' => 'URL slug', 'rules' => 'nullable|max:200'],
            ['name' => 'blog_category_id', 'label' => 'Category', 'type' => 'select', 'options' => M\BlogCategory::orderBy('name')->pluck('name', 'id')->all(), 'rules' => 'nullable|exists:blog_categories,id', 'empty' => 'Uncategorised'],
            ['name' => 'author_id', 'label' => 'Author', 'type' => 'select', 'options' => M\User::whereNotNull('role_id')->orderBy('name')->pluck('name', 'id')->all(), 'rules' => 'nullable|exists:users,id', 'empty' => 'Me'],
            ['name' => 'featured_image', 'label' => 'Featured image', 'type' => 'image'],
            ['name' => 'excerpt', 'label' => 'Excerpt', 'type' => 'textarea', 'rules' => 'nullable|max:500'],
            ['name' => 'content', 'label' => 'Content (use the link button for internal links)', 'type' => 'richtext', 'rules' => 'nullable'],
            ['name' => 'tags_text', 'label' => 'Tags (comma separated)', 'virtual' => true, 'default' => $m?->tags->pluck('name')->implode(', ')],
            ['name' => 'is_published', 'label' => 'Published', 'type' => 'checkbox', 'default' => false],
            ['name' => 'published_at', 'label' => 'Publish date (future date = scheduled)', 'type' => 'datetime', 'rules' => 'nullable|date'],
        ];
    }
    protected function columns(): array
    {
        return ['Title' => 'title', 'Category' => fn ($p) => $p->category?->name ?? '—', 'Author' => fn ($p) => $p->author?->name ?? '—',
            'Status' => fn ($p) => !$p->is_published ? 'Draft' : ($p->published_at && $p->published_at->isFuture() ? 'Scheduled ' . $p->published_at->format('d M') : 'Published')];
    }
    protected function beforeSave(array &$data, Request $r, ?Model $m): void
    {
        $data['author_id'] = $data['author_id'] ?: ($m?->author_id ?? auth()->id());
        if (!empty($data['is_published']) && empty($data['published_at'])) $data['published_at'] = now();
    }
    protected function afterSave(Model $m, array $v, Request $r): void
    {
        $ids = collect(explode(',', (string) ($v['tags_text'] ?? '')))->map(fn ($t) => trim($t))->filter()->unique()
            ->map(fn ($t) => M\BlogTag::firstOrCreate(['slug' => Str::slug($t)], ['name' => $t])->id);
        $m->tags()->sync($ids);
    }
}
