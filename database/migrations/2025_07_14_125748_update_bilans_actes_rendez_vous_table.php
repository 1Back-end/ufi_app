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
        Schema::table('bilans_actes_rendez_vous', function (Blueprint $table) {
            // Supprimer la colonne acte_id
            $table->dropForeign(['acte_id']);
            $table->dropColumn('acte_id');

            // Ajouter la nouvelle colonne prestation_id
            $table->foreignId('prestation_id')
                ->after('rendez_vous_id')
                ->constrained('prestations')
                ->onDelete('cascade');
        });

        //
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bilans_actes_rendez_vous', function (Blueprint $table) {
            // Supprimer prestation_id et restaurer acte_id
            $table->dropForeign(['prestation_id']);
            $table->dropColumn('prestation_id');

            $table->foreignId('acte_id')
                ->constrained('actes')
                ->onDelete('cascade');
        });

        //
    }
};
