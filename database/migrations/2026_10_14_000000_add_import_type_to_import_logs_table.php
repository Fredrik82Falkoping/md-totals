<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_logs', function (Blueprint $table) {
            $table->string('import_type')->default('markdown')->after('tenant_id');
            $table->index(['import_type', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::table('import_logs', function (Blueprint $table) {
            $table->dropIndex(['import_type', 'started_at']);
            $table->dropColumn('import_type');
        });
    }
};
