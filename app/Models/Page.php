<?php
namespace App\Models;

use App\Models\Concerns\HasSeo;

class Page extends Model
{
    use HasSeo;
    protected $casts = ['is_published' => 'boolean'];
}
