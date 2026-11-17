<?php
namespace App\Models;

class Customer extends Model
{
    public function user() { return $this->belongsTo(User::class); }
    public function orders() { return $this->hasMany(Order::class); }
    public function addresses() { return $this->hasMany(Address::class); }
    public function getTotalSpentAttribute(): float
    {
        return (float) $this->orders()->whereNotIn('status', ['cancelled', 'refunded'])->sum('total');
    }
}
