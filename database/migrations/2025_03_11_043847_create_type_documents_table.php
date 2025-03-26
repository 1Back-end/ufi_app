<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('type_documents', function (Blueprint $table) {
            $table->id();
            $table->string('description_typedoc');
            $table->foreignId('create_by')->references('id')->on('users')->restrictOnDelete();
            $table->foreignId('update_by')->references('id')->on('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('type_documents');
    }
};
