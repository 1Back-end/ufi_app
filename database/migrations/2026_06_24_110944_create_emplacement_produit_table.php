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
        Schema::create('emplacement_produit', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_produit');
            $table->unsignedBigInteger('id_emplacement');

            $table->foreign('id_produit')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('id_emplacement')->references('id')->on('emplacements_products')->onDelete('cascade');

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
        Schema::dropIfExists('emplacement_produit');
    }
};
