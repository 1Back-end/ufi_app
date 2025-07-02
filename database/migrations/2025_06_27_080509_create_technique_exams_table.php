<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('technique_exams', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('analysis_technique_id')->constrained('analysis_techniques')->references('id')->restrictOnDelete();
            $table->string('type')->default('default');
            $table->foreignId('created_by')->constrained('users')->references('id')->restrictOnDelete();
            $table->foreignId('updated_by')->constrained('users')->references('id')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technique_exams');
    }
};
