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
            $table->foreignId('rapport_consultation_id')
                ->nullable()
                ->constrained('ops_tbl_rapport_consultations')
                ->nullOnDelete(); // ou restrictOnDelete() selon le comportement souhaité
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ops_tbl_rapport_consultations', function (Blueprint $table) {
            $table->dropForeign(['rapport_consultation_id']);
            $table->dropColumn('rapport_consultation_id');
        });
    }
};
