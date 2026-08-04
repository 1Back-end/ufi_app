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
        Schema::create('sortie_en_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained('lot_produits')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('type'); // ex: 'vente', 'entree', 'ajustement'
            $table->integer('quantity'); // Quantité mouvementée
            $table->integer('stock_before'); // Stock avant opération
            $table->integer('stock_after'); // Stock après opération
            $table->foreignId('prestation_id')->constrained('prestations')->onDelete('cascade');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sortie_en_stock');
    }
};
