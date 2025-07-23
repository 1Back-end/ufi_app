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
        Schema::table('ops_tbl_mise_en_observation_hospitalisation', function (Blueprint $table) {
            $table->string('type_observation')
                ->default('J')
                ->comment('J = journalière, H = hospitalisation')
                ->after('resume');

            $table->integer('nbre_heures')
                ->nullable()
                ->after('type_observation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ops_tbl_mise_en_observation_hospitalisation', function (Blueprint $table) {
            $table->dropColumn(['type_observation', 'nbre_heures']);
        });
    }
};
