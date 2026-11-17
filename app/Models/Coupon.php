<?php
namespace App\Models;

class Coupon extends Model
{
    protected $casts = ['starts_at' => 'datetime', 'expires_at' => 'datetime', 'is_active' => 'boolean', 'amount' => 'float', 'min_spend' => 'float', 'max_discount' => 'float'];
    public function products() { return $this->belongsToMany(Product::class, 'coupon_product'); }
    public function categories() { return $this->belongsToMany(Category::class, 'coupon_category'); }
    public function usages() { return $this->hasMany(CouponUsage::class); }
}
