<?php
namespace App\Models;

class SeoMetadata extends Model
{
    protected $table = 'seo_metadata';
    protected $casts = ['noindex' => 'boolean'];
    public function seoable() { return $this->morphTo(); }
}
