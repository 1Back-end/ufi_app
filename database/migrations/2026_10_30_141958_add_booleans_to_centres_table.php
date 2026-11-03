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
        Schema::table('centres', function (Blueprint $table) {
            $table->boolean('used_for_product_cmgt')->default(false)->after('id');
            $table->boolean('used_for_product_gt_labo')->default(false)->after('used_for_product_cmgt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('centres', function (Blueprint $table) {
            $table->dropColumn([
                'used_for_product_cmgt',
                'used_for_product_gt_labo'
            ]);
        });
    }
};
