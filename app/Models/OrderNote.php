<?php
namespace App\Models;

class OrderNote extends Model
{
    public function order() { return $this->belongsTo(Order::class); }
    public function user() { return $this->belongsTo(User::class); }
}
