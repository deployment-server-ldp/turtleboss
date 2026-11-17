<?php
namespace App\Models;

class InventoryLog extends Model
{
    public function product() { return $this->belongsTo(Product::class)->withTrashed(); }
    public function variant() { return $this->belongsTo(ProductVariant::class); }
    public function user() { return $this->belongsTo(User::class); }
}
