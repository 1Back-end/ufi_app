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
        Schema::table('consultants', function (Blueprint $table) {
            $table->string('tel1')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Étape 1 : Remplacer les NULL par une valeur par défaut avant de changer le schéma
        DB::table('consultants')->whereNull('tel1')->update(['tel1' => '']);

        // Étape 2 : Changer la colonne pour la rendre NOT NULL
        Schema::table('consultants', function (Blueprint $table) {
            $table->string('tel1')->nullable(false)->change();
        });
    }
};
