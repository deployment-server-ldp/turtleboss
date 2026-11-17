<?php
namespace App\Models;

class WishlistItem extends Model
{
    public function product() { return $this->belongsTo(Product::class); }
    public function wishlist() { return $this->belongsTo(Wishlist::class); }
}
