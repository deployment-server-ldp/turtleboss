<?php
namespace App\Models;

class Wishlist extends Model
{
    public function items() { return $this->hasMany(WishlistItem::class); }
    public function products() { return $this->belongsToMany(Product::class, 'wishlist_items')->withTimestamps(); }
}
