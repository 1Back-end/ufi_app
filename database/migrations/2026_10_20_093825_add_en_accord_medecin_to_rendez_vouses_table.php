<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rendez_vouses', function (Blueprint $table) {
            $table->boolean('doctor_approved')->default(false)->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('rendez_vouses', function (Blueprint $table) {
            $table->dropColumn('doctor_approved');
        });
    }
};
