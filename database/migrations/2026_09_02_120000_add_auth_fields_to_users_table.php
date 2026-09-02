<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->foreignId('tenant_id')->nullable()->after('password')->constrained('tenants')->nullOnDelete();
            $table->boolean('is_admin')->default(false)->after('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'tenant_id', 'is_admin']);
        });
    }
};
