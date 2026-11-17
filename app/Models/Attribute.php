<?php
namespace App\Models;

class Attribute extends Model
{
    public function values() { return $this->hasMany(AttributeValue::class)->orderBy('sort_order')->orderBy('id'); }
}
