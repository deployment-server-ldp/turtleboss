<?php
namespace App\Http\Controllers\Admin;

use App\Models as M;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends ResourceController
{
    protected string $model = M\Category::class; protected string $route = 'admin.categories'; protected string $title = 'Categories'; protected string $singular = 'Category';
    protected bool $seo = true; protected string $orderBy = 'sort_order'; protected string $orderDir = 'asc'; protected string $images = 'categories'; protected array $with = ['parent'];
    protected function fields(?Model $m): array
    {
        $parents = M\Category::when($m, fn ($q) => $q->where('id', '!=', $m->id))->orderBy('name')->pluck('name', 'id')->all();
        return [
            ['name' => 'name', 'label' => 'Name', 'rules' => 'required|max:120'],
            ['name' => 'slug', 'label' => 'URL slug', 'rules' => 'nullable|max:150', 'help' => 'Leave empty to generate from the name.'],
            ['name' => 'parent_id', 'label' => 'Parent category (makes this a subcategory)', 'type' => 'select', 'options' => $parents, 'rules' => 'nullable|exists:categories,id', 'empty' => 'None (top level)'],
            ['name' => 'image', 'label' => 'Category image', 'type' => 'image'],
            ['name' => 'description', 'label' => 'Description', 'type' => 'richtext', 'rules' => 'nullable'],
            ['name' => 'sort_order', 'label' => 'Display order', 'type' => 'number', 'rules' => 'nullable|integer', 'default' => 0],
            ['name' => 'is_active', 'label' => 'Enabled (visible on the store)', 'type' => 'checkbox', 'default' => true],
        ];
    }
    protected function columns(): array
    {
        return ['Name' => fn ($c) => ($c->parent ? $c->parent->name . ' › ' : '') . $c->name, 'Slug' => 'slug', 'Order' => 'sort_order', 'Products' => fn ($c) => $c->products()->count(), 'Status' => fn ($c) => $c->is_active ? 'Enabled' : 'Disabled'];
    }
    protected function beforeSave(array &$data, Request $r, ?Model $m): void
    {
        if ($m && !empty($data['parent_id']) && in_array((int) $data['parent_id'], $m->selfAndDescendantIds())) abort(422, 'A category cannot be its own parent.');
        $data['sort_order'] = $data['sort_order'] ?? 0;
    }
    protected function canDelete(Model $m): ?string { return $m->children()->exists() ? 'Delete or move the subcategories first.' : null; }
}
