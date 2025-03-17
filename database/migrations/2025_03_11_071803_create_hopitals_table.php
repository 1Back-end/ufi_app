<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('hopitals', function (Blueprint $table) {
            $table->id();
            $table->string('nom_hopi');
            $table->string('Abbreviation_hopi');
            $table->string('addresse_hopi');
            $table->foreignId('create_by_hopi')->nullable()->default(null)->references('id')->on('users')->onDelete('restrict');
            $table->foreignId('update_by_hopi')->nullable()->default(null)->references('id')->on('users')->onDelete('restrict');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hopitals');
    }
};
