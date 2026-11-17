<?php
namespace App\Models;

class Role extends Model
{
    public function permissions() { return $this->belongsToMany(Permission::class); }
    public function users() { return $this->hasMany(User::class); }
}
