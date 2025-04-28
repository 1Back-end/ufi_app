<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assurables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assureur_id')->references('id')->on('assureurs')->cascadeOnDelete();
            $table->morphs('assurable');
            $table->integer('k_modulateur')->nullable();
            $table->integer('b')->nullable();
            $table->integer('pu')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assurables');
        Schema::dropIfExists('assureur_acte');
    }
};
