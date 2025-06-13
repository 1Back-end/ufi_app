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
        Schema::create('ops_tbl__examen__physiques', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('libelle')->nullable();      // Intitulé ou libellé de l'examen
            $table->text('resultat')->nullable();       // Résultats détaillés / compte-rendu clinique
            $table->foreignId('motif_consultation_id')->nullable()->constrained('ops_tbl__motif_consultations')->restrictOnDelete();
            $table->foreignId('categorie_examen_physique_id')->nullable()->constrained('config_tbl_categories_examen_physiques')->restrictOnDelete();
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
        Schema::dropIfExists('ops_tbl__examen__physiques');
    }
};
