<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\MarkdownController;
use App\Http\Controllers\TenantController;

Route::get('/', [TenantController::class, 'index'])->name('tenants.select');
Route::post('/select-tenant', [TenantController::class, 'store'])->name('tenants.store');

Route::get('/statistics', [MarkdownController::class, 'index'])->name('statistics.index');

Route::get('/statistics/product/{productId}', [MarkdownController::class, 'productDetail'])
    ->name('statistics.product-detail');
