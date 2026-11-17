<?php
namespace App\Models;

use App\Models\Concerns\HasSeo;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    use HasSeo;
    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        $flush = fn () => Cache::forget('nav.categories');
        static::saved($flush);
        static::deleted($flush);
    }
    public function parent() { return $this->belongsTo(Category::class, 'parent_id'); }
    public function children() { return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order'); }
    public function products() { return $this->belongsToMany(Product::class); }
    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeRoots($q) { return $q->whereNull('parent_id'); }
    public function getUrlAttribute(): string { return route('category.show', $this->slug); }
    /** IDs of this category plus all descendants */
    public function selfAndDescendantIds(): array
    {
        $ids = [$this->id];
        $frontier = [$this->id];
        while ($frontier) {
            $frontier = Category::whereIn('parent_id', $frontier)->pluck('id')->all();
            $ids = array_merge($ids, $frontier);
        }
        return $ids;
    }
}
