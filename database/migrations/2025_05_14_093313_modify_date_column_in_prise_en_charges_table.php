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
        Schema::table('prise_en_charges', function (Blueprint $table) {
            $table->timestamp('date')->useCurrent()->change(); // Définit la valeur par défaut comme l'heure actuelle
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prise_en_charges', function (Blueprint $table) {
            $table->timestamp('date')->change(); // Revenir à un timestamp classique sans valeur par défaut
        });
    }
};
