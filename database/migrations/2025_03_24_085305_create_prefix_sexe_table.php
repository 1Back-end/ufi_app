<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('prefix_sexe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prefix_id')->references('id')->on('prefixes')->cascadeOnDelete();
            $table->foreignId('sexe_id')->references('id')->on('sexes')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prefix_sexe');
    }
};
