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
        Schema::table('dossier_consultations', function (Blueprint $table) {
            $table->boolean('is_have_motif_consultation')->default(false)->after('id');
            $table->boolean('is_have_antecedent')->default(false)->after('is_have_motif_consultation');
            $table->boolean('is_have_examen_physique')->default(false)->after('is_have_antecedent');
            $table->boolean('is_have_rapport_consultation')->default(false)->after('is_have_examen_physique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dossier_consultations', function (Blueprint $table) {
            $table->dropColumn([
                'is_have_motif_consultation',
                'is_have_antecedent',
                'is_have_examen_physique',
                'is_have_rapport_consultation',
            ]);
        });
    }
};
