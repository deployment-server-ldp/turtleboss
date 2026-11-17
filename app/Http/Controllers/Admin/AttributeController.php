<?php
namespace App\Http\Controllers\Admin;

use App\Models as M;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AttributeController extends ResourceController
{
    protected string $model = M\Attribute::class; protected string $route = 'admin.attributes'; protected string $title = 'Product Attributes'; protected string $singular = 'Attribute';
    protected string $orderBy = 'sort_order'; protected string $orderDir = 'asc';
    protected function fields(?Model $m): array
    {
        $values = $m ? $m->values->map(fn ($v) => $v->value . ($v->color_hex ? '|' . $v->color_hex : ''))->implode("\n") : '';
        return [
            ['name' => 'name', 'label' => 'Attribute name (e.g. Color, Size, Material, Weight)', 'rules' => 'required|max:80'],
            ['name' => 'slug', 'label' => 'Slug', 'rules' => 'nullable|max:100'],
            ['name' => 'type', 'label' => 'Display type', 'type' => 'select', 'rules' => 'required|in:select,color,text', 'options' => ['select' => 'Buttons / dropdown', 'color' => 'Color swatches', 'text' => 'Text']],
            ['name' => 'values_text', 'label' => 'Values (one per line; for colors use  Name|#hex)', 'type' => 'textarea', 'virtual' => true, 'default' => $values, 'rows' => 8],
            ['name' => 'is_filterable', 'label' => 'Show as a filter on the Shop page', 'type' => 'checkbox', 'default' => true],
            ['name' => 'sort_order', 'label' => 'Display order', 'type' => 'number', 'rules' => 'nullable|integer', 'default' => 0],
        ];
    }
    protected function columns(): array { return ['Name' => 'name', 'Type' => 'type', 'Values' => fn ($a) => $a->values->pluck('value')->implode(', ')]; }
    
    
    protected function beforeSave(array &$data, Request $r, ?Model $m): void { $data['sort_order'] = $data['sort_order'] ?? 0; }
    protected function afterSave(Model $m, array $v, Request $r): void
    {
        $keep = [];
        foreach (preg_split('/\R/', (string) ($v['values_text'] ?? '')) as $i => $line) {
            $line = trim($line); if ($line === '') continue;
            [$val, $hex] = array_pad(explode('|', $line, 2), 2, null);
            $hex = $hex && preg_match('/^#[0-9a-f]{3,8}$/i', trim($hex)) ? trim($hex) : null;
            $row = $m->values()->updateOrCreate(['value' => trim($val)], ['color_hex' => $hex, 'sort_order' => $i]);
            $keep[] = $row->id;
        }
        $m->values()->whereNotIn("id", $keep)->whereNotIn("id", \DB::table("variant_attribute_values")->select("attribute_value_id"))->delete();
    }
    protected function canDelete(Model $m): ?string { return null; }
}
