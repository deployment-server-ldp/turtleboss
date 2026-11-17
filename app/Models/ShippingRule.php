<?php
namespace App\Models;

class ShippingRule extends Model
{
    protected $casts = ['is_active' => 'boolean', 'rate' => 'float', 'free_over' => 'float'];
}
