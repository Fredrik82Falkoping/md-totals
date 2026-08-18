<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}