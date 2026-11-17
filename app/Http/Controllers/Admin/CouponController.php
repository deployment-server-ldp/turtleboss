<?php
namespace App\Http\Controllers\Admin;

use App\Models as M;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CouponController extends ResourceController
{
    protected string $model = M\Coupon::class; protected string $route = 'admin.coupons'; protected string $title = 'Coupons & Discounts'; protected string $singular = 'Coupon';
    protected array $searchable = ['code', 'description'];
    protected function fields(?Model $m): array
    {
        return [
            ['name' => 'code', 'label' => 'Coupon code', 'rules' => 'required|max:60|unique:coupons,code' . ($m ? ',' . $m->id : '')],
            ['name' => 'description', 'label' => 'Description (internal)', 'rules' => 'nullable|max:200'],
            ['name' => 'type', 'label' => 'Discount type', 'type' => 'select', 'rules' => 'required|in:percent,fixed,free_shipping', 'options' => ['percent' => 'Percentage discount', 'fixed' => 'Fixed amount (Rs.)', 'free_shipping' => 'Free shipping']],
            ['name' => 'amount', 'label' => 'Discount amount (% or Rs.)', 'type' => 'number', 'rules' => 'nullable|numeric|min:0', 'default' => 0, 'step' => '0.01'],
            ['name' => 'min_spend', 'label' => 'Minimum spend (Rs.)', 'type' => 'number', 'rules' => 'nullable|numeric|min:0', 'step' => '0.01'],
            ['name' => 'max_discount', 'label' => 'Maximum discount (Rs.)', 'type' => 'number', 'rules' => 'nullable|numeric|min:0', 'step' => '0.01'],
            ['name' => 'usage_limit', 'label' => 'Total usage limit', 'type' => 'number', 'rules' => 'nullable|integer|min:1'],
            ['name' => 'per_user_limit', 'label' => 'Limit per customer', 'type' => 'number', 'rules' => 'nullable|integer|min:1'],
            ['name' => 'starts_at', 'label' => 'Start date', 'type' => 'datetime', 'rules' => 'nullable|date'],
            ['name' => 'expires_at', 'label' => 'Expiry date', 'type' => 'datetime', 'rules' => 'nullable|date|after_or_equal:starts_at'],
            ['name' => 'product_ids', 'label' => 'Eligible products (empty = all)', 'type' => 'multiselect', 'virtual' => true, 'options' => M\Product::orderBy('name')->pluck('name', 'id')->all(), 'default' => $m?->products->pluck('id')->all() ?? []],
            ['name' => 'category_ids', 'label' => 'Eligible categories (empty = all)', 'type' => 'multiselect', 'virtual' => true, 'options' => M\Category::orderBy('name')->pluck('name', 'id')->all(), 'default' => $m?->categories->pluck('id')->all() ?? []],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => true],
        ];
    }
    protected function columns(): array
    {
        return ['Code' => 'code', 'Type' => fn ($c) => ucfirst(str_replace('_', ' ', $c->type)), 'Amount' => fn ($c) => $c->type === 'percent' ? $c->amount . '%' : ($c->type === 'fixed' ? money($c->amount) : '—'),
            'Used' => fn ($c) => $c->used_count . ($c->usage_limit ? ' / ' . $c->usage_limit : ''), 'Expires' => fn ($c) => $c->expires_at?->format('d M Y') ?? 'Never', 'Status' => fn ($c) => $c->is_active ? 'Active' : 'Inactive'];
    }
    protected function beforeSave(array &$data, Request $r, ?Model $m): void { $data['code'] = strtoupper(trim($data['code'])); $data['amount'] = $data['amount'] ?? 0; if (($data['type'] ?? '') === 'percent' && $data['amount'] > 100) abort(422, 'Percentage cannot exceed 100.'); }
    protected function afterSave(Model $m, array $v, Request $r): void { $m->products()->sync($v['product_ids'] ?? []); $m->categories()->sync($v['category_ids'] ?? []); }
}
