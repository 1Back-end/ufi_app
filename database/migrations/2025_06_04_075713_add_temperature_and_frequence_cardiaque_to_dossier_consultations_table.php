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
            $table->string('temperature')->nullable()->after('saturation');
            $table->string('frequence_cardiaque')->nullable()->after('temperature');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dossier_consultations', function (Blueprint $table) {
            $table->dropColumn(['temperature', 'frequence_cardiaque']);
        });
    }
};
