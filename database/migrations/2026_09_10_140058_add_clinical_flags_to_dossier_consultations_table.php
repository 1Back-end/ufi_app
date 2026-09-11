<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dossier_consultations', function (Blueprint $table) {
            if (!Schema::hasColumn('dossier_consultations', 'is_have_enquete_systemique')) {
                $table->boolean('is_have_enquete_systemique')->default(false)->after('is_have_examen_physique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dossier_consultations', function (Blueprint $table) {
            $table->dropColumn([
                'is_have_enquete_systemique'
            ]);
        });
    }
};
