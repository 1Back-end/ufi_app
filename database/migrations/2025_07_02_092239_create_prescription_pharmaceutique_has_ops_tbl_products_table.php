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
        Schema::create('prescription_pharmaceutique_has_ops_tbl_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prescription_pharmaceutique_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantite')->default(1);
            $table->timestamps();

            // 👇 Foreign keys avec noms personnalisés
            $table->foreign('prescription_pharmaceutique_id', 'fk_prescription_pharma')
                ->references('id')
                ->on('prescription_pharmaceutiques')
                ->onDelete('cascade');

            $table->foreign('product_id', 'fk_product')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescription_pharmaceutique_has_ops_tbl_products');
    }
};
