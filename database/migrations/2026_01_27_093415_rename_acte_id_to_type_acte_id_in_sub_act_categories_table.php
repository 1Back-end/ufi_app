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
        Schema::table('sub_act_categories', function (Blueprint $table) {
            $table->dropForeign(['acte_id']);

            // Renommer la colonne
            $table->renameColumn('acte_id', 'type_acte_id');

            // Ajouter la nouvelle clé étrangère
            $table->foreign('type_acte_id')->references('id')->on('type_actes')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sub_act_categories', function (Blueprint $table) {
            $table->dropForeign(['type_acte_id']);

            // Renommer la colonne en acte_id
            $table->renameColumn('type_acte_id', 'acte_id');

            // Ajouter la clé étrangère précédente
            $table->foreign('acte_id')->references('id')->on('actes')->cascadeOnDelete();
        });
    }
};
