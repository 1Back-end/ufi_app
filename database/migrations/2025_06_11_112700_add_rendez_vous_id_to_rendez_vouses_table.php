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
            // Ajoute la colonne auto-référencée
            $table->unsignedBigInteger('rendez_vous_id')->nullable()->after('id');

            // Déclare la clé étrangère vers la même table
            $table->foreign('rendez_vous_id')->references('id')->on('rendez_vouses')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rendez_vouses', function (Blueprint $table) {
            $table->dropForeign(['rendez_vous_id']);
            $table->dropColumn('rendez_vous_id');
        });
    }
};
