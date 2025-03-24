<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sexe_stat_fam', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sex_id')->references('id')->on('sexes')->cascadeOnDelete();
            $table->foreignId('stat_fam_id')->references('id')->on('status_familiales')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sexe_stat_fam');
    }
};
