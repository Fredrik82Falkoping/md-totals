<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = ['name', 'store_code'];

    public function markdowns()
    {
        return $this->hasMany(Markdown::class);
    }
}