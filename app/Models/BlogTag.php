<?php
namespace App\Models;

class BlogTag extends Model { public function posts() { return $this->belongsToMany(BlogPost::class); } }
