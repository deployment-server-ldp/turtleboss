<?php
namespace App\Models;

class Address extends Model
{
    protected $casts = ['is_default' => 'boolean'];
    public function customer() { return $this->belongsTo(Customer::class); }
}
