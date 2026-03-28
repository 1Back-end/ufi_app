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
        Schema::table('session_caisse', function (Blueprint $table) {
            // 🔹 Supprimer les colonnes existantes
            $table->dropColumn(['fonds_ouverture', 'fonds_fermeture', 'fonds_en_pause', 'solde']);
        });

        Schema::table('session_caisse', function (Blueprint $table) {
            // 🔹 Recréer les colonnes en integer (stockage en centimes)
            $table->integer('fonds_ouverture')->default(0);
            $table->integer('fonds_fermeture')->default(0);
            $table->integer('fonds_en_pause')->default(0);
            $table->integer('solde')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('session_caisse', function (Blueprint $table) {
            // 🔹 Supprimer les colonnes integer
            $table->dropColumn(['fonds_ouverture', 'fonds_fermeture', 'fonds_en_pause', 'solde']);
        });

        Schema::table('session_caisse', function (Blueprint $table) {
            // 🔹 Recréer les colonnes en decimal(10,2)
            $table->decimal('fonds_ouverture', 10, 2)->default(0);
            $table->decimal('fonds_fermeture', 10, 2)->default(0);
            $table->decimal('fonds_en_pause', 10, 2)->default(0);
            $table->decimal('solde', 10, 2)->default(0);
        });
    }
};
