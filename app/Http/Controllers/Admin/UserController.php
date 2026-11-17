<?php
namespace App\Http\Controllers\Admin;

use App\Models as M;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserController extends ResourceController
{
    protected string $model = M\User::class; protected string $route = 'admin.users'; protected string $title = 'Users & Roles'; protected string $singular = 'Staff user';
    protected array $searchable = ['name', 'email']; protected array $with = ['role'];
    protected function filter($q, Request $r): void { $q->whereNotNull('role_id'); }
    protected function fields(?Model $m): array
    {
        return [
            ['name' => 'name', 'label' => 'Full name', 'rules' => 'required|max:100'],
            ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'rules' => 'required|email|max:150|unique:users,email' . ($m ? ',' . $m->id : '')],
            ['name' => 'phone', 'label' => 'Phone', 'rules' => 'nullable|max:32'],
            ['name' => 'role_id', 'label' => 'Role', 'type' => 'select', 'options' => M\Role::orderBy('id')->pluck('name', 'id')->all(), 'rules' => 'required|exists:roles,id'],
            ['name' => 'password', 'label' => $m ? 'New password (leave empty to keep)' : 'Password', 'type' => 'password', 'rules' => ($m ? 'nullable' : 'required') . '|min:8'],
            ['name' => 'is_active', 'label' => 'Account active', 'type' => 'checkbox', 'default' => true],
        ];
    }
    protected function columns(): array { return ['Name' => 'name', 'Email' => 'email', 'Role' => fn ($u) => $u->role?->name, 'Last login' => fn ($u) => $u->last_login_at?->diffForHumans() ?? 'Never', 'Status' => fn ($u) => $u->is_active ? 'Active' : 'Disabled']; }
    protected function beforeSave(array &$data, Request $r, ?Model $m): void
    {
        if ($m && $m->id === auth()->id() && (empty($data['is_active']) || (int) $data['role_id'] !== (int) $m->role_id)) abort(422, 'You cannot disable or change the role of your own account.');
    }
    protected function canDelete(Model $m): ?string { return $m->id === auth()->id() ? 'You cannot delete your own account.' : null; }
    protected function afterSave(Model $m, array $v, Request $r): void { cache()->forget('role.perms.' . $m->role_id); }
}
