<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_centre', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->unsignedBigInteger('centre_id');
            $table->foreign('centre_id')->references('id')->on('centres')->restrictOnDelete();
            $table->boolean('default')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_centre');
    }
};
