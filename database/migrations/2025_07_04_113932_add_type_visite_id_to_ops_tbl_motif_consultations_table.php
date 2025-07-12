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
            $table->foreignId('type_visite_id')
                ->nullable()
                ->after('description')
                ->constrained('config_tbl_type_visite')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ops_tbl__motif_consultations', function (Blueprint $table) {
            $table->dropForeign(['type_visite_id']);
            $table->dropColumn('type_visite_id');
        });
    }
};
