<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'tenant_id',
        'product_id',
        'group_key',
        'name',
    ];
}
