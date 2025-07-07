<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('examens', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->float('price');
            $table->string('b');
            $table->string('b1');
            $table->integer('renderer_duration')->nullable();
            $table->string('name_abrege')->nullable();
            $table->float('prelevement_unit')->nullable();
            $table->string('name1')->nullable();
            $table->string('name2')->nullable();
            $table->string('name3')->nullable();
            $table->string('name4')->nullable();
            $table->foreignId('kb_prelevement_id')->constrained('kb_prelevements')->references('id')->restrictOnDelete();
            $table->foreignId('tube_prelevement_id')->constrained('tube_prelevements')->references('id')->restrictOnDelete();
            $table->foreignId('type_prelevement_id')->constrained('type_prelevements')->references('id')->restrictOnDelete();
            $table->foreignId('paillasse_id')->constrained('paillasses')->references('id')->restrictOnDelete();
            $table->foreignId('sub_family_exam_id')->constrained('sub_family_exams')->references('id')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examens');
    }
};
