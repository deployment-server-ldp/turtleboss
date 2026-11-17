<?php
namespace App\Http\Controllers\Admin;

use App\Models as M;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogCategoryController extends ResourceController
{
    protected string $model = M\BlogCategory::class; protected string $route = 'admin.blog-categories'; protected string $title = 'Blog Categories'; protected string $singular = 'Blog category';
    protected function fields(?Model $m): array { return [['name' => 'name', 'label' => 'Name', 'rules' => 'required|max:100'], ['name' => 'slug', 'label' => 'Slug', 'rules' => 'nullable|max:120']]; }
    protected function columns(): array { return ['Name' => 'name', 'Slug' => 'slug', 'Posts' => fn ($c) => $c->posts()->count()]; }
}
