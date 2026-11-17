<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    protected $guarded = [];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = ['email_verified_at' => 'datetime', 'password' => 'hashed', 'is_active' => 'boolean', 'last_login_at' => 'datetime'];

    public function role() { return $this->belongsTo(Role::class); }
    public function customer() { return $this->hasOne(Customer::class); }
    public function isStaff(): bool { return $this->role_id !== null; }
    public function isSuperAdmin(): bool { return $this->role?->slug === 'super_admin'; }

    public function hasPermission(string $slug): bool
    {
        if (!$this->role) return false;
        if ($this->role->slug === 'super_admin') return true;
        $perms = cache()->remember('role.perms.' . $this->role_id, 300, fn () => $this->role->permissions()->pluck('slug')->all());
        return in_array($slug, $perms, true);
    }
}
