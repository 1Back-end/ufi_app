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
        Schema::table('consultant_disponibilites', function (Blueprint $table) {
            // Modifier le type de 'jour' en tinyInteger
            $table->tinyInteger('jour')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultant_disponibilites', function (Blueprint $table) {
            $table->string('jour')->change();
        });
    }
};
