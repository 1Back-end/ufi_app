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
        Schema::table('products', function (Blueprint $table) {
            $table->string('barcode')->nullable()->after('name')->unique();
            $table->decimal('pharmacy_price', 15, 2)->default(0.00)->after('price');

            $table->foreignId('product_type_id')
                ->nullable()
                ->after('id')
                ->constrained('product_types')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['product_type_id']);
            $table->dropColumn(['barcode', 'pharmacy_price', 'product_type_id']);
        });
    }
};
