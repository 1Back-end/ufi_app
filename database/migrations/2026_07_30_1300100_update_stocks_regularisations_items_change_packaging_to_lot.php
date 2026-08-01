<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks_regularisations_items', function (Blueprint $table) {
            $table->dropForeign(['packaging_id']);
            $table->dropColumn('packaging_id');

            $table->foreignId('lot_id')
                ->nullable()
                ->after('product_id')
                ->constrained('lot_produits')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stocks_regularisations_items', function (Blueprint $table) {
            $table->dropForeign(['lot_id']);
            $table->dropColumn('lot_id');

            $table->foreignId('packaging_id')
                ->nullable()
                ->constrained('packagings')
                ->nullOnDelete();
        });
    }
};
