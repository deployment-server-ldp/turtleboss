<?php
namespace App\Models;

class BlogCategory extends Model { public function posts() { return $this->hasMany(BlogPost::class); } }
