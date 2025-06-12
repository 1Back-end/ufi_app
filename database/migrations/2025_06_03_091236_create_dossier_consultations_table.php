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
        Schema::create('dossier_consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facture_id')->nullable()->constrained('factures')->restrictOnDelete();
            $table->foreignId('rendez_vous_id')->nullable()->constrained('rendez_vouses')->restrictOnDelete();
            $table->string('poids')->nullable();
            $table->string('tension')->nullable();
            $table->string('taille')->nullable();
            $table->string('saturation')->nullable();
            $table->boolean('is_deleted')->default(false);
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
        Schema::dropIfExists('dossier_consultations');
    }
};
