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
        Schema::table('consultant_disponibilites', function (Blueprint $table) {
            // On supprime l'ancienne colonne 'heure'
            $table->dropColumn('heure');

            // Ajout des nouvelles colonnes pour gérer une plage horaire
            $table->time('heure_debut')->after('jour');
            $table->time('heure_fin')->after('heure_debut');
        });
        //
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultant_disponibilites', function (Blueprint $table) {
            // Rollback : supprimer les nouvelles colonnes
            $table->dropColumn(['heure_debut', 'heure_fin']);

            // Réajout de l'ancienne colonne
            $table->time('heure')->nullable()->after('jour');
        });
        //
    }
};
