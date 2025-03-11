<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('societes', function (Blueprint $table) {
            $table->id();
            $table->unique('nom_soc_cli');
            $table->string('tel_soc_cli')->nullable();
            $table->string('Adress_soc_cli')->nullable();
            $table->string('num_contrib_soc_cli')->nullable();
            $table->string('email_soc_cli')->nullable();
            $table->foreignId('create_by_soc_cli');
            $table->foreignId('updated_by_soc_cli');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('societes');
    }
};
