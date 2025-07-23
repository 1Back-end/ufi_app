<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestation_id')->constrained('prestations')->references('id')->onDelete('cascade');
            $table->foreignId('element_paillasse_id')->constrained('element_paillasses')->references('id')->onDelete('cascade');
            $table->foreignId('groupe_population_id')->nullable()->constrained('groupe_populations')->references('id')->onDelete('cascade');
            $table->string('result_machine');
            $table->string('result_client');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
