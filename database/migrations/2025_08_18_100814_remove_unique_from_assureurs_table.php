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
            // Supprimer les index uniques
            $table->dropUnique('assureurs_reg_com_unique');
            $table->dropUnique('assureurs_num_com_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assureurs', function (Blueprint $table) {
            $table->unique('Reg_com');
            $table->unique('num_com');
        });
    }
};
