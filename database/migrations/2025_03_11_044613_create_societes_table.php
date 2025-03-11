<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('societes', function (Blueprint $table) {
            $table->id();
            $table->string('nom_soc_cli');
            $table->string('tel_soc_cli')->nullable();
            $table->string('adress_soc_cli')->nullable();
            $table->string('num_contrib_soc_cli')->nullable();
            $table->string('email_soc_cli')->nullable();
            $table->foreignId('create_by')->references('id')->on('users')->restrictOnDelete();
            $table->foreignId('updated_by')->references('id')->on('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique('nom_soc_cli');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('societes');
    }
};
