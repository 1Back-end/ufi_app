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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('ref');
            $table->string('name');
            $table->string('dosage');
            $table->foreignId('voix_transmissions_id')->constrained('voix_transmissions')->onDelete('cascade');
            $table->float('price');
            $table->foreignId('unite_produits_id')->constrained('unite_produits')->onDelete('cascade');
            $table->foreignId('group_products_id')->constrained('group_products')->onDelete('cascade');
            $table->integer('unite_par_emballage');
            $table->integer('condition_par_unite_emballage');
            $table->string('Dosage_defaut');
            $table->string('schema_administration');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_deleted')->default(false);
            $table->string('status')->default('Actif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
