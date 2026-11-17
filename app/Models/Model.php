<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Base;

abstract class Model extends Base
{
    protected $guarded = [];
}
