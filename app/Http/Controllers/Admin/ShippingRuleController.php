<?php
namespace App\Http\Controllers\Admin;

use App\Models as M;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShippingRuleController extends ResourceController
{
    protected string $model = M\ShippingRule::class; protected string $route = 'admin.shipping-rules'; protected string $title = 'Shipping Rules'; protected string $singular = 'Shipping rule';
    protected string $orderBy = 'priority'; protected string $orderDir = 'asc';
    protected function fields(?Model $m): array
    {
        return [
            ['name' => 'name', 'label' => 'Rule name', 'rules' => 'required|max:100'],
            ['name' => 'city', 'label' => 'City (empty = all cities)', 'type' => 'select', 'options' => array_combine(\App\Services\ShippingService::cities(), \App\Services\ShippingService::cities()), 'rules' => 'nullable|max:80', 'empty' => 'All cities'],
            ['name' => 'rate', 'label' => 'Shipping charge (Rs.)', 'type' => 'number', 'rules' => 'required|numeric|min:0', 'step' => '0.01'],
            ['name' => 'free_over', 'label' => 'Free shipping when order is at least (Rs.)', 'type' => 'number', 'rules' => 'nullable|numeric|min:0', 'step' => '0.01'],
            ['name' => 'priority', 'label' => 'Priority (lower = checked first)', 'type' => 'number', 'rules' => 'nullable|integer', 'default' => 10],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => true],
        ];
    }
    protected function columns(): array { return ['Rule' => 'name', 'City' => fn ($r) => $r->city ?: 'All cities', 'Charge' => fn ($r) => money($r->rate), 'Free over' => fn ($r) => $r->free_over ? money($r->free_over) : '—', 'Status' => fn ($r) => $r->is_active ? 'Active' : 'Off']; }
    protected function beforeSave(array &$data, Request $r, ?Model $m): void { $data['priority'] = $data['priority'] ?? 10; }
}
