<?php
namespace App\Http\Controllers\Admin;

use App\Models as M;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends ResourceController
{
    protected string $model = M\Page::class; protected string $route = 'admin.pages'; protected string $title = 'Pages'; protected string $singular = 'Page';
    protected bool $seo = true; protected array $searchable = ['title', 'slug']; protected string $images = 'pages';
    protected function fields(?Model $m): array
    {
        return [
            ['name' => 'title', 'label' => 'Title', 'rules' => 'required|max:160'],
            ['name' => 'slug', 'label' => 'URL slug', 'rules' => 'nullable|max:150|not_in:admin,shop,cart,checkout,account,login,register,blog,category,product,wishlist,search', 'help' => 'Page is served at /your-slug'],
            ['name' => 'template', 'label' => 'Template', 'type' => 'select', 'rules' => 'required|in:default,contact,faq', 'options' => ['default' => 'Default', 'contact' => 'Contact (adds contact form)', 'faq' => 'FAQ']],
            ['name' => 'banner', 'label' => 'Page banner image', 'type' => 'image'],
            ['name' => 'content', 'label' => 'Content', 'type' => 'richtext', 'rules' => 'nullable'],
            ['name' => 'is_published', 'label' => 'Published', 'type' => 'checkbox', 'default' => true],
        ];
    }
    protected function columns(): array { return ['Title' => 'title', 'URL' => fn ($p) => '/' . $p->slug, 'Status' => fn ($p) => $p->is_published ? 'Published' : 'Draft', 'Updated' => fn ($p) => $p->updated_at->diffForHumans()]; }
}
