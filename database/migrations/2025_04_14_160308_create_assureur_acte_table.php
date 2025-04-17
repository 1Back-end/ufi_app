<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assureur_acte', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assureur_id');
            $table->unsignedBigInteger('acte_id');
            $table->integer('k_modulateur')->default(0);
            $table->integer('b')->default(0);
            $table->integer('b1')->default(0);

            $table->foreign('assureur_id')->references('id')->on('assureurs')->restrictOnDelete();
            $table->foreign('acte_id')->references('id')->on('actes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assureur_acte');
    }
};
