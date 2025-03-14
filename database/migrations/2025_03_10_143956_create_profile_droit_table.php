<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('profile_droit', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('profile_id');
            $table->foreign('profile_id')->references('id')->on('profiles')->onDelete('cascade');
            $table->unsignedBigInteger('droit_id');
            $table->foreign('droit_id')->references('id')->on('droits')->onDelete('cascade');
            $table->timestamp('date_creation_profile_droit');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_droit');
    }
};
