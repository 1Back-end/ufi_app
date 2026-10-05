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
            $table->foreignId('kit_id')
                ->nullable()
                ->after('id')
                ->constrained('kit_products')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['kit_id']);
            $table->dropColumn('kit_id');

            // Si vous avez ajouté is_kit :
            // $table->dropColumn('is_kit');
        });
    }
};
