<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('specialites', function (Blueprint $table) {
            $table->id();
            $table->string('nom_specialite');
            $table->foreignId('create_by_specialite')->references('id')->on('users')->Ondelete('restrict');
            $table->foreignId('update_by_specialite')->references('id')->on('users')->Ondelete('restrict');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('specialites');
    }
};
