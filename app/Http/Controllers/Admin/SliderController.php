<?php
namespace App\Http\Controllers\Admin;

use App\Models as M;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SliderController extends ResourceController
{
    protected string $model = M\Slider::class; protected string $route = 'admin.sliders'; protected string $title = 'Slider Management'; protected string $singular = 'Slide';
    protected string $orderBy = 'sort_order'; protected string $orderDir = 'asc'; protected array $searchable = ['heading']; protected string $images = 'sliders';
    protected function fields(?Model $m): array
    {
        return [
            ['name' => 'heading', 'label' => 'Heading', 'rules' => 'nullable|max:160'],
            ['name' => 'subheading', 'label' => 'Subheading', 'rules' => 'nullable|max:255'],
            ['name' => 'cta_text', 'label' => 'Button text', 'rules' => 'nullable|max:40'],
            ['name' => 'cta_url', 'label' => 'Button URL (e.g. /shop or /category/toys)', 'rules' => 'nullable|max:255'],
            ['name' => 'image_desktop', 'label' => 'Desktop image (recommended 1920×700)', 'type' => 'image'],
            ['name' => 'image_mobile', 'label' => 'Mobile image (recommended 900×1100)', 'type' => 'image'],
            ['name' => 'alt', 'label' => 'Image ALT text', 'rules' => 'nullable|max:160'],
            ['name' => 'sort_order', 'label' => 'Display order', 'type' => 'number', 'rules' => 'nullable|integer', 'default' => 0],
            ['name' => 'starts_at', 'label' => 'Show from', 'type' => 'datetime', 'rules' => 'nullable|date'],
            ['name' => 'ends_at', 'label' => 'Show until', 'type' => 'datetime', 'rules' => 'nullable|date|after_or_equal:starts_at'],
            ['name' => 'is_active', 'label' => 'Enabled', 'type' => 'checkbox', 'default' => true],
        ];
    }
    protected function columns(): array { return ['Image' => fn ($s) => ['img', img($s->image_desktop)], 'Heading' => 'heading', 'Order' => 'sort_order', 'Schedule' => fn ($s) => ($s->starts_at?->format('d M') ?? '—') . ' → ' . ($s->ends_at?->format('d M') ?? '—'), 'Status' => fn ($s) => $s->is_active ? 'Enabled' : 'Disabled']; }
    protected function beforeSave(array &$data, Request $r, ?Model $m): void { $data['sort_order'] = $data['sort_order'] ?? 0; }
}
