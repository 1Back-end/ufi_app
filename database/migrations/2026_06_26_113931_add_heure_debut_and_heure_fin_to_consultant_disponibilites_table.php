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
        Schema::table('consultant_disponibilites', function (Blueprint $table) {
            $table->time('heure_debut')->nullable()->after('id');
            $table->time('heure_fin')->nullable()->after('heure_debut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultant_disponibilites', function (Blueprint $table) {
            $table->dropColumn(['heure_debut', 'heure_fin']);
        });
    }
};
