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
        Schema::table('ops_tbl_rapport_consultations', function (Blueprint $table) {
            $table->foreignId('dossier_consultation_id')
                ->nullable()
                ->constrained('dossier_consultations')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ops_tbl_rapport_consultations', function (Blueprint $table) {
            $table->dropForeign(['dossier_consultation_id']);
            $table->dropColumn('dossier_consultation_id');
        });
    }
};
