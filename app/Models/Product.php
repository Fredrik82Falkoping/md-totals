<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'store_key',
        'article_key',
        'group_key',
        'name',
    ];
}
