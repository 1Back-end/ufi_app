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
        Schema::create('infirmiere_mise_observation', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mise_observation_id');
            $table->unsignedBigInteger('infirmiere_id');

            // Clés étrangères
            $table->foreign('mise_observation_id')
                ->references('id')
                ->on('ops_tbl_mise_en_observation_hospitalisation')
                ->onDelete('cascade');

            $table->foreign('infirmiere_id')
                ->references('id')
                ->on('nurses')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('infirmiere_mise_observation');
    }
};
