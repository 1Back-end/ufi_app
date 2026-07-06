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
        Schema::table('lot_produits', function (Blueprint $table) {
            $table->foreignId('fournisseur_id')
                ->nullable()
                ->constrained('fournisseurs')
                ->nullOnDelete()
                ->after('product_id');

            $table->text('justification')
                ->nullable()
                ->after('fournisseur_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lot_produits', function (Blueprint $table) {
            $table->dropForeign(['fournisseur_id']);
            $table->dropColumn(['fournisseur_id', 'justification']);
        });
    }
};
