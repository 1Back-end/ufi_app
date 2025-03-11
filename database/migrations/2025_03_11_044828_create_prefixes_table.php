<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('prefixes', function (Blueprint $table) {
            $table->id();
            $table->string('prefixe');
            $table->foreignId('create_by_prefix');
            $table->foreignId('update_by_prefix');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prefixes');
    }
};
