<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('normal_value', function (Blueprint $table) {
            $table->id();
            $table->foreignId('element_paillasse_id')->constrained('element_paillasses')->references('id')->onDelete('cascade');
            $table->foreignId('groupe_population_id')->constrained('groupe_populations')->references('id')->onDelete('cascade');
            $table->float('value');
            $table->float('value_max')->nullable();
            $table->string('sign');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('normal_value');
    }
};
