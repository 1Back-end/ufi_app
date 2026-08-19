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
        Schema::create('lot_produit_conditionnement', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('lot_produit_id');
            $table->foreign('lot_produit_id')
                ->references('id')
                ->on('lot_produits')
                ->onDelete('cascade');

            $table->unsignedBigInteger('product_packagings_id');
            $table->foreign('product_packagings_id')
                ->references('id')
                ->on('packagings')
                ->onDelete('cascade');

            $table->decimal('quantite', 10, 2)->default(1);
            $table->decimal('price', 12, 2)->nullable();

            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lot_produit_conditionnement');
    }
};
