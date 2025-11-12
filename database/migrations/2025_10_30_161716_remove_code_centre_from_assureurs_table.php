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
        Schema::table('assureurs', function (Blueprint $table) {
            $table->dropForeign(['code_centre']); // Supprimer la contrainte étrangère
            $table->dropColumn('code_centre');    // Supprimer la colonne
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assureurs', function (Blueprint $table) {
            $table->foreignId('code_centre')
                ->constrained('centres')
                ->onDelete('cascade'); // Ré-ajouter la colonne et la contrainte
        });
    }
};
