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
            $table->decimal('taux_retenu', 5, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assureurs', function (Blueprint $table) {
            $table->integer('taux_retenu')->change();
        });
    }
};
