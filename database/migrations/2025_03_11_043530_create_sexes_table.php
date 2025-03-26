<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sexes', function (Blueprint $table) {
            $table->id();
            $table->string('description_sex');
            $table->foreignId('create_by')->references('id')->on('users')->restrictOnDelete();
            $table->foreignId('update_by')->references('id')->on('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sexes');
    }
};
