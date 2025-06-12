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
            Schema::table('rendez_vouses', function (Blueprint $table) {
                // Supprimer d'abord la contrainte de clé étrangère
                $table->dropForeign('rendez_vouses_facture_id_foreign');

                // Ensuite, supprimer la colonne
                $table->dropColumn('facture_id');

                // Ajouter la colonne prestation_id
                $table->unsignedBigInteger('prestation_id')->nullable()->after('id');

                // Ajouter la clé étrangère vers prestations
                $table->foreign('prestation_id')->references('id')->on('prestations')->onDelete('set null');
            });
        });

        //
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rendez_vouses', function (Blueprint $table) {
            // Supprimer la nouvelle clé étrangère
            $table->dropForeign(['prestation_id']);

            // Supprimer la colonne ajoutée
            $table->dropColumn('prestation_id');

            // Restaurer la colonne facture_id
            $table->unsignedBigInteger('facture_id')->nullable();

            // Restaurer sa contrainte
            $table->foreign('facture_id')->references('id')->on('factures')->onDelete('set null');
        });
    }
};
