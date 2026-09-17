<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->unsignedTinyInteger('group_key');
            $table->string('name');
            $table->timestamps();

            // Samma group_key kan finnas för olika tenants, men bara en gång per tenant
            $table->unique(['tenant_id', 'group_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
