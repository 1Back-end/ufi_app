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
        Schema::table('assureurs', function (Blueprint $table) {
            $table->dropUnique('assureurs_tel_unique');
            $table->dropUnique('assureurs_tel1_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assureurs', function (Blueprint $table) {
            $table->unique('tel');
            $table->unique('tel1');
        });
    }
};
