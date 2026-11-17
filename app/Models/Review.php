<?php
namespace App\Models;

class Review extends Model
{
    protected $casts = ['is_verified' => 'boolean', 'is_approved' => 'boolean'];
    public function product() { return $this->belongsTo(Product::class)->withTrashed(); }
    public function user() { return $this->belongsTo(User::class); }
    protected static function booted(): void
    {
        static::saved(fn ($r) => $r->product?->refreshRating());
        static::deleted(fn ($r) => $r->product?->refreshRating());
    }
}
