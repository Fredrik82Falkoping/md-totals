<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $table = 'import_logs';

    protected $fillable = [
        'tenant_id',
        'imported_count',
        'status',
        'error',
        'started_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}