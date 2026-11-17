<?php
namespace App\Models;

use Illuminate\Support\Facades\Cache;

class Menu extends Model
{
    public function items() { return $this->hasMany(MenuItem::class)->orderBy('sort_order'); }
    public function roots() { return $this->hasMany(MenuItem::class)->whereNull('parent_id')->orderBy('sort_order'); }
    /** Active, nested items for a menu location (cached). */
    public static function tree(string $location)
    {
        return Cache::remember("menu.$location", 600, function () use ($location) {
            $menu = static::where('location', $location)->first();
            if (!$menu) return collect();
            return $menu->roots()->where('is_active', true)->with(['children' => fn ($q) => $q->where('is_active', true)->with(['children' => fn ($q) => $q->where('is_active', true)])])->get();
        });
    }
}
