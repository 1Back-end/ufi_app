<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('prestationables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestation_id')->references('id')->on('prestations')->restrictOnDelete();
            $table->morphs('prestationable');
            $table->integer('remise')->default(0);
            $table->integer('quantity')->nullable();
            $table->dateTime('date_rdv')->nullable();
            $table->integer('nbr_days')->nullable();
            $table->integer('type_salle')->nullable();
            $table->integer('honoraire')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestationables');
    }
};
