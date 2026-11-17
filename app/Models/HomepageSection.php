<?php
namespace App\Models;

class HomepageSection extends Model
{
    protected $casts = ['config' => 'array', 'is_active' => 'boolean'];
}
