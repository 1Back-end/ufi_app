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
        Schema::create('lot_produits', function (Blueprint $table) {
            $table->id();

            $table->string('numero_lot_fabricant');
            $table->date('date_peremption')->nullable();
            $table->date('date_reception')->nullable();

            $table->integer('quantite_actuelle')->default(0);

            $table->string('statut',)->default('Disponible');

            $table->unsignedBigInteger('id_produit');
            $table->unsignedBigInteger('id_emplacement');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_produit')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            $table->foreign('id_emplacement')
                ->references('id')
                ->on('emplacements_products')
                ->onDelete('cascade');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lot_produits');
    }
};
