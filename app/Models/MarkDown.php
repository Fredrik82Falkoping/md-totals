<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;


// app/Models/Markdown.php
class Markdown extends Model
{
    protected $fillable = [
        'product_id', 'name', 'k_id', 'category', 'scanned_at', 'month', 'week',
        'quantity', 'weight_kg', 'regular_price', 'reduced_price', 'discount_amount',
        'discount_percent', 'purchase_price', 'margin_amount', 'margin_percent',
        'tenant_id',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'regular_price' => 'decimal:2',
        'reduced_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percent' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (session()->has('tenant_id')) {
                $builder->where('tenant_id', session('tenant_id'));
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

}