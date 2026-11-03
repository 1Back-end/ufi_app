<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rendez_vouses', function (Blueprint $table) {
            $table->dateTime('date_expiration')
                ->storedAs('DATE_ADD(dateheure_rdv, INTERVAL 14 DAY)')
                ->nullable()
                ->after('dateheure_rdv');
        });
    }

    public function down(): void
    {
        Schema::table('rendez_vouses', function (Blueprint $table) {
            $table->dropColumn('date_expiration');
        });
    }
};
