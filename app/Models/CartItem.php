<?php
namespace App\Models;

class CartItem extends Model
{
    public function cart() { return $this->belongsTo(Cart::class); }
    public function product() { return $this->belongsTo(Product::class)->withTrashed(); }
    public function variant() { return $this->belongsTo(ProductVariant::class); }
}
