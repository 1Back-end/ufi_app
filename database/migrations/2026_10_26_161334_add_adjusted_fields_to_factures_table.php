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
        // 1. Suppression sécurisée pour la table factures
        try {
            DB::statement('ALTER TABLE factures DROP FOREIGN KEY factures_adjusted_by_foreign');
        } catch (\Exception $e) {
            // Ignore si la clé n'existe pas
        }

        try {
            DB::statement('ALTER TABLE factures DROP COLUMN adjusted_by');
        } catch (\Exception $e) {
            // Ignore si la colonne n'existe pas
        }

        try {
            DB::statement('ALTER TABLE factures DROP COLUMN adjusted_at');
        } catch (\Exception $e) {
            // Ignore si la colonne n'existe pas
        }

        // 2. Ajout propre des colonnes et de la clé étrangère
        Schema::table('factures', function (Blueprint $table) {
            $table->timestamp('adjusted_at')->nullable();
            $table->foreignId('adjusted_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE factures DROP FOREIGN KEY factures_adjusted_by_foreign');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE factures DROP COLUMN adjusted_by');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE factures DROP COLUMN adjusted_at');
        } catch (\Exception $e) {}
    }
};
