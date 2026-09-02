<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = ['name', 'store_code', 'api_endpoint', 'last_synced_at'];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public function markdowns()
    {
        return $this->hasMany(Markdown::class);
    }
}