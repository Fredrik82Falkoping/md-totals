<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('markdowns', function (Blueprint $table) {
            $table->string('source_key')->nullable()->unique()->after('id');   // strKey
            $table->string('reason')->nullable();                              // enReason
            $table->boolean('is_group')->default(false);                       // bIsGroup
            $table->unsignedBigInteger('group_key')->nullable();                // ulGroupKey (kategori-ID)
            $table->boolean('variable_quantity')->default(false);               // bPriceType
            $table->string('unit_of_measure')->nullable();                      // enUOM
            $table->unsignedInteger('packs')->default(1);                       // ulPacks
            $table->decimal('markdown_value', 12, 2)->nullable();                // ulMdVal (skalad vid import)
            $table->decimal('cost_price_by_portion', 12, 2)->nullable();         // dCostPriceByPortion
            $table->string('currency', 8)->nullable();                           // strCurrency
        });
    }

    public function down(): void
    {
        Schema::table('markdowns', function (Blueprint $table) {
            $table->dropColumn([
                'source_key', 'reason', 'is_group', 'group_key',
                'variable_quantity', 'unit_of_measure', 'packs',
                'markdown_value', 'cost_price_by_portion', 'currency', 'is_deleted',
            ]);
        });
    }
};
