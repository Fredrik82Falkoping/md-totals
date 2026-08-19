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
        Schema::create('markdowns', function (Blueprint $table) {
            $table->id();
            $table->string('product_id');              // stored as string due to leading apostrophe / long number in source
            $table->string('name')->nullable();
            $table->string('k_id')->nullable();          // meaning unclear - confirm with colleague (checkout ID? customer ID?)
            $table->string('category')->nullable();
            $table->timestamp('scanned_at');              // was "Date"
            $table->string('month')->nullable();
            $table->string('week')->nullable();
            $table->decimal('quantity', 10, 2)->nullable();      // "St."
            $table->decimal('weight_kg', 10, 3)->nullable();
            $table->decimal('regular_price', 10, 2);
            $table->decimal('reduced_price', 10, 2);
            $table->decimal('discount_amount', 10, 2);
            $table->decimal('discount_percent', 5, 2);
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->decimal('margin_amount', 10, 2)->nullable();
            $table->decimal('margin_percent', 5, 2)->nullable();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants');   // added once tenant structure is finalized
            $table->timestamps();

            $table->unique(['product_id', 'scanned_at', 'tenant_id'], 'unique_product_scanned_tenant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('markdowns');
    }
};
