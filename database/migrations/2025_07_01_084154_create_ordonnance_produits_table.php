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
        Schema::create('ordonnance_produits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ordonnance_id')
                ->constrained('ops_tbl_ordonnance')
                ->cascadeOnDelete();
            $table->string('nom');
            $table->integer('quantite')->default(1);
            $table->string('protocole');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordonnance_produits');
    }
};
