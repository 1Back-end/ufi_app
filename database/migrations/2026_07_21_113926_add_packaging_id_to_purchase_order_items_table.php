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
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->foreignId('packaging_id')
                ->nullable()
                ->after('product_id')
                ->constrained('packagings')
                ->nullOnDelete();

            if (Schema::hasColumn('purchase_order_items', 'conditionnement')) {
                $table->dropColumn('conditionnement');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropForeign(['packaging_id']);
            $table->dropColumn('packaging_id');
            $table->string('conditionnement')->nullable()->after('product_id');
        });
    }
};
