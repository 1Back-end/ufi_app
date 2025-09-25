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
        Schema::table('dossier_consultations', function (Blueprint $table) {
            $table->dropColumn('tension');

            // Ajouter les nouvelles colonnes
            $table->string('tension_arterielle_bg')->nullable();
            $table->string('tension_arterielle_bd')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dossier_consultations', function (Blueprint $table) {
            $table->string('tension')->nullable();

            // Supprimer les nouvelles colonnes
            $table->dropColumn(['tension_arterielle_bg', 'tension_arterielle_bd']);
        });
    }
};
