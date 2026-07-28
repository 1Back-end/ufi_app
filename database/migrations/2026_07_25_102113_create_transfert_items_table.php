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
        Schema::create('transfert_stocks_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfert_id')->constrained('transferts_stocks')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('lot_produit_id')->constrained('lot_produits');
            $table->integer('quantite')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfert_stocks_items');
    }
};
