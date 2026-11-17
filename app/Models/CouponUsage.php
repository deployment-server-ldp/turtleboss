<?php
namespace App\Models;

class CouponUsage extends Model
{
    public function coupon() { return $this->belongsTo(Coupon::class); }
    public function order() { return $this->belongsTo(Order::class); }
}
