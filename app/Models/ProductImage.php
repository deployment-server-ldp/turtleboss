<?php
namespace App\Models;

class ProductImage extends Model
{
    public function product() { return $this->belongsTo(Product::class); }
    public function getUrlAttribute(): string { return img($this->path); }
}
