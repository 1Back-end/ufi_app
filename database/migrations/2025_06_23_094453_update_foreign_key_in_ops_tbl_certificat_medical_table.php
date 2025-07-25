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
        Schema::table('ops_tbl_certificat_medical', function (Blueprint $table) {
            $table->dropForeign(['motif_consultation_id']);
            $table->dropColumn('motif_consultation_id');

            // Ajouter la nouvelle clé étrangère
            $table->foreignId('rapport_consultation_id')
                ->nullable()
                ->constrained('ops_tbl_rapport_consultations')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ops_tbl_certificat_medical', function (Blueprint $table) {
            $table->dropForeign(['rapport_consultation_id']);
            $table->dropColumn('rapport_consultation_id');

            // Restaurer l'ancienne clé étrangère
            $table->foreignId('motif_consultation_id')
                ->nullable()
                ->constrained('ops_tbl__motif_consultations')
                ->restrictOnDelete();
        });
    }
};
