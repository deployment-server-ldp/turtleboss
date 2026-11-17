<?php
namespace App\Models;

class OrderItem extends Model
{
    public $timestamps = false;
    public function order() { return $this->belongsTo(Order::class); }
    public function product() { return $this->belongsTo(Product::class)->withTrashed(); }
}
