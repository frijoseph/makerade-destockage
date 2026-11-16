<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];
    protected $hidden = ['id', 'created_at', 'updated_at'];
    protected $casts = ['price' => 'float', 'old_price' => 'float', 'rating' => 'float', 'is_new' => 'bool', 'hot' => 'bool'];
}
