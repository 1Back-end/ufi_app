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
            $table->boolean('is_open')->default(true)->after('is_deleted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dossier_consultations', function (Blueprint $table) {
            $table->dropColumn('is_open');
        });
    }
};
