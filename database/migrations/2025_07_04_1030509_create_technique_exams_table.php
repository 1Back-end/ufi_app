<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('technique_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_technique_id')->constrained('analysis_techniques')->references('id')->restrictOnDelete();
            $table->foreignId('examen_id')->constrained('examens')->restrictOnDelete();
            $table->boolean('type')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technique_exams');
    }
};
