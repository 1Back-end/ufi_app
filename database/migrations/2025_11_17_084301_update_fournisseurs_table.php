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
        Schema::table('fournisseurs', function (Blueprint $table) {

            // Retirer changement en boolean — on laisse tel quel

            // Suppression sécurisée des colonnes
            if (Schema::hasColumn('fournisseurs', 'fax')) {
                $table->dropColumn('fax');
            }

            if (Schema::hasColumn('fournisseurs', 'state')) {
                $table->dropColumn('state');
            }

            // Ajouts
            if (!Schema::hasColumn('fournisseurs', 'registre_commerce')) {
                $table->string('registre_commerce')->nullable();
            }

            if (!Schema::hasColumn('fournisseurs', 'nui')) {
                $table->string('nui')->nullable();
            }

            if (!Schema::hasColumn('fournisseurs', 'personne_contact_1')) {
                $table->string('personne_contact_1')->nullable();
                $table->string('telephone_contact_1')->nullable();
            }

            if (!Schema::hasColumn('fournisseurs', 'personne_contact_2')) {
                $table->string('personne_contact_2')->nullable();
                $table->string('telephone_contact_2')->nullable();
            }

            if (!Schema::hasColumn('fournisseurs', 'directeur_general')) {
                $table->string('directeur_general')->nullable();
            }

            // Mettre le status par défaut en "actif" minuscule
            if (Schema::hasColumn('fournisseurs', 'status')) {
                $table->string('status')->default('actif')->change();
            }

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fournisseurs', function (Blueprint $table) {

            // Restaurer fax
            if (!Schema::hasColumn('fournisseurs', 'fax')) {
                $table->string('fax')->nullable();
            }

            // Restaurer state
            if (!Schema::hasColumn('fournisseurs', 'state')) {
                $table->string('state')->nullable();
            }

            // Restaurer l'ancien default du status
            if (Schema::hasColumn('fournisseurs', 'status')) {
                $table->string('status')->default('Actif')->change();
            }

            // Supprimer les nouvelles colonnes
            $colonnes = [
                'registre_commerce',
                'nui',
                'personne_contact_1',
                'telephone_contact_1',
                'personne_contact_2',
                'telephone_contact_2',
                'directeur_general'
            ];

            foreach ($colonnes as $col) {
                if (Schema::hasColumn('fournisseurs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
