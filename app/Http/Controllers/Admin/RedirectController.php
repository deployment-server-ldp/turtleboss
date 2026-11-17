<?php
namespace App\Http\Controllers\Admin;

use App\Models as M;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RedirectController extends ResourceController
{
    protected string $model = M\Redirect::class; protected string $route = 'admin.redirects'; protected string $title = 'Redirects'; protected string $singular = 'Redirect'; protected array $searchable = ['from_path', 'to_url'];
    protected function fields(?Model $m): array
    {
        return [
            ['name' => 'from_path', 'label' => 'Old path (e.g. /old-product)', 'rules' => 'required|max:255|unique:redirects,from_path' . ($m ? ',' . $m->id : '')],
            ['name' => 'to_url', 'label' => 'New URL or path', 'rules' => 'required|max:500'],
            ['name' => 'status_code', 'label' => 'Type', 'type' => 'select', 'rules' => 'required|in:301,302', 'options' => [301 => '301 Permanent', 302 => '302 Temporary']],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => true],
        ];
    }
    protected function columns(): array { return ['From' => 'from_path', 'To' => 'to_url', 'Type' => 'status_code', 'Hits' => 'hits', 'Status' => fn ($r) => $r->is_active ? 'Active' : 'Off']; }
    protected function beforeSave(array &$data, Request $r, ?Model $m): void { $data['from_path'] = '/' . trim($data['from_path'], '/'); }
}
