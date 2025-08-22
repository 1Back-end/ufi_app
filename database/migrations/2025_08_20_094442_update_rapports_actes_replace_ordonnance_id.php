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
        Schema::table('rapports_actes', function (Blueprint $table) {
            // Supprimer la colonne ordonnance_id et sa contrainte
            $table->dropForeign(['ordonnance_id']);
            $table->dropColumn('ordonnance_id');

            // Ajouter la nouvelle colonne rapport_consultation_id
            $table->foreignId('rapport_consultation_id')
                ->nullable()
                ->constrained('ops_tbl_rapport_consultations')
                ->cascadeOnDelete();
        });
        //
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rapports_actes', function (Blueprint $table) {
            // Supprimer la colonne rapport_consultation_id
            $table->dropForeign(['rapport_consultation_id']);
            $table->dropColumn('rapport_consultation_id');

            // Remettre l'ancienne colonne ordonnance_id
            $table->foreignId('ordonnance_id')
                ->nullable()
                ->constrained('ops_tbl_ordonnance')
                ->cascadeOnDelete();
        });
    }
        //
};
