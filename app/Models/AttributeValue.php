<?php
namespace App\Models;

class AttributeValue extends Model
{
    public function attribute() { return $this->belongsTo(Attribute::class); }
}
