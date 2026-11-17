<?php
namespace App\Models;

use Illuminate\Support\Facades\Cache;

class MenuItem extends Model
{
    protected $casts = ['is_active' => 'boolean', 'open_new_tab' => 'boolean'];
    protected static function booted(): void
    {
        $flush = fn ($i) => Cache::forget('menu.' . $i->menu?->location);
        static::saved($flush);
        static::deleted($flush);
    }
    public function menu() { return $this->belongsTo(Menu::class); }
    public function children() { return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('sort_order'); }
    public function parent() { return $this->belongsTo(MenuItem::class, 'parent_id'); }
    public function getHrefAttribute(): string { return str_starts_with($this->url, 'http') ? $this->url : url($this->url); }
}
