<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('markdowns', function (Blueprint $table) {
            $table->dropUnique('unique_product_scanned_tenant');
        });
    }

    public function down(): void
    {
        Schema::table('markdowns', function (Blueprint $table) {
            $table->unique(
                ['product_id', 'scanned_at', 'tenant_id'],
                'unique_product_scanned_tenant'
            );
        });
    }
};