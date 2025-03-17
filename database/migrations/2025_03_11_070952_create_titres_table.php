<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('titres', function (Blueprint $table) {
            $table->id();
            $table->string('nom_titre');
            $table->string('abbreviation_titre');
            $table->foreignId('create_by')->nullable()->default(null)->references('id')->on('users')->OnDelete('cascade');
            $table->foreignId('update_by')->nullable()->default(null)->references('id')->on('users')->OnDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('titres');
    }
};
