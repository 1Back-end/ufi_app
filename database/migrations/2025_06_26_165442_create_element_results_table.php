<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('element_results', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->foreignId('created_by')->constrained('users')->references('id')->restrictOnDelete();
            $table->foreignId('updated_by')->constrained('users')->references('id')->restrictOnDelete();
            $table->foreignId('category_element_result_id')->constrained('category_element_results')->references('id')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('element_results');
    }
};
