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
        Schema::table('rendez_vouses', function (Blueprint $table) {
            // Supprime la colonne date_emission
            $table->dropColumn('date_emission');

            // Ajoute la colonne duree (en minutes)
            $table->integer('duration')->after('dateheure_rdv');
        });
        //
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rendez_vouses', function (Blueprint $table) {
            // Ajoute à nouveau date_emission (si on rollback)
            $table->dateTime('date_emission')->nullable();

            // Supprime la colonne duree
            $table->dropColumn('duration');
        });
        //
    }
};
