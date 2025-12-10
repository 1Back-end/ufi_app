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
        Schema::table('prestations', function (Blueprint $table) {
            $table->unsignedBigInteger('campagne_id')->nullable()->after('type');

            // Si tu veux ajouter la contrainte de clé étrangère
            $table->foreign('campagne_id')->references('id')->on('campagnes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestations', function (Blueprint $table) {
            $table->dropForeign(['campagne_id']);
            $table->dropColumn('campagne_id');
        });
    }
};
