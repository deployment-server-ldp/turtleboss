<?php
namespace App\Models;

class Cart extends Model
{
    public function items() { return $this->hasMany(CartItem::class)->with(['product.images', 'product.variants', 'variant.values.attribute', 'variant.images']); }
}
