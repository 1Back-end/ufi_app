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
        Schema::table('ops_tbl_antecedents', function (Blueprint $table) {
            // Vérifie si la colonne n'existe pas déjà pour éviter les doublons
            if (!Schema::hasColumn('ops_tbl_antecedents', 'dossier_consultation_id')) {
                $table->unsignedBigInteger('dossier_consultation_id')->nullable()->after('id'); // Ajuste 'after' si besoin

                $table->foreign('dossier_consultation_id', 'fk_ops_antecedents_dossier_consultation')
                    ->references('id')
                    ->on('dossiers_consultations')
                    ->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ops_tbl_antecedents', function (Blueprint $table) {
            if (Schema::hasColumn('ops_tbl_antecedents', 'dossier_consultation_id')) {
                $table->dropForeign('fk_ops_antecedents_dossier_consultation');
                $table->dropColumn('dossier_consultation_id');
            }
        });
    }
};
