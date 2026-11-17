<?php
namespace App\Models;

use App\Models\Concerns\HasSeo;

class BlogPost extends Model
{
    use HasSeo;
    protected $casts = ['is_published' => 'boolean', 'published_at' => 'datetime'];
    public function category() { return $this->belongsTo(BlogCategory::class, 'blog_category_id'); }
    public function author() { return $this->belongsTo(User::class, 'author_id'); }
    public function tags() { return $this->belongsToMany(BlogTag::class); }
    public function scopeLive($q) { return $q->where('is_published', true)->where(fn ($w) => $w->whereNull('published_at')->orWhere('published_at', '<=', now())); }
}
