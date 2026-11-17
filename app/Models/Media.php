<?php
namespace App\Models;

class Media extends Model
{
    protected $table = 'media';
    public function getUrlAttribute(): string { return img($this->path); }
}
