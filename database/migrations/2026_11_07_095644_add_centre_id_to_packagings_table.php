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
        // S'assurer que la colonne n'existe pas déjà pour éviter les erreurs
        if (!Schema::hasColumn('packagings', 'centre_id')) {
            Schema::table('packagings', function (Blueprint $table) {
                $table->foreignId('centre_id')
                    ->nullable() // Indispensable s'il y a déjà des enregistrements dans la table
                    ->after('id')
                    ->constrained('centres')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packagings', function (Blueprint $table) {
            if (Schema::hasColumn('packagings', 'centre_id')) {
                $table->dropForeign(['centre_id']);
                $table->dropColumn('centre_id');
            }
        });
    }
};
