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
        Schema::table('ops_tbl__motif_consultations', function (Blueprint $table) {
            $table->foreignId('categorie_visite_id')
                ->nullable()
                ->constrained('config_tbl_categorie_visites')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ops_tbl__motif_consultations', function (Blueprint $table) {
            $table->dropForeign(['categorie_visite_id']);
            $table->dropColumn('categorie_visite_id');
        });
    }
};
