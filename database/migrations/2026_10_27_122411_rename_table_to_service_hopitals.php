<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Désactive temporairement les vérifications de clés étrangères pour renommer en toute sécurité
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Détecte le nom actuel possible de la table
        $currentName = null;
        if (Schema::hasTable('service__hopitals')) {
            $currentName = 'service__hopitals';
        } elseif (Schema::hasTable('consultants_hospitals')) {
            $currentName = 'consultants_hospitals';
        } elseif (Schema::hasTable('hopitals')) {
            $currentName = 'hopitals';
        } elseif (Schema::hasTable('hospitals')) {
            $currentName = 'hospitals';
        }

        // Renomme vers consultants_service_hospitals si elle existe sous un autre nom
        if ($currentName && $currentName !== 'consultants_service_hospitals') {
            Schema::rename($currentName, 'consultants_service_hospitals');
        }

        // Mise à jour de la structure de la table consultants_service_hospitals
        if (Schema::hasTable('consultants_service_hospitals')) {
            Schema::table('consultants_service_hospitals', function (Blueprint $table) {
                // 1. Supprimer le champ is_deleted s'il existe
                if (Schema::hasColumn('consultants_service_hospitals', 'is_deleted')) {
                    $table->dropColumn('is_deleted');
                }

                // 2. Ajouter is_active s'il n'existe pas
                if (!Schema::hasColumn('consultants_service_hospitals', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }

                // 3. Ajouter created_by s'il n'existe pas
                if (!Schema::hasColumn('consultants_service_hospitals', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable();
                    $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                }

                // 4. Ajouter updated_by s'il n'existe pas
                if (!Schema::hasColumn('consultants_service_hospitals', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable();
                    $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
                }

                // 5. Ajouter softDeletes (deleted_at) s'il n'existe pas
                if (!Schema::hasColumn('consultants_service_hospitals', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        if (Schema::hasTable('consultants_service_hospitals')) {
            Schema::rename('consultants_service_hospitals', 'consultants_hospitals');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};
