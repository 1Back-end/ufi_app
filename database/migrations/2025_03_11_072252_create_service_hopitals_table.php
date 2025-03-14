<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('service__hopitals', function (Blueprint $table) {
            $table->id();
            $table->string('nom_service_hopi');
            $table->foreignId('create_by_service_hopi')->references('id')->on('users')->Ondelete('restrict');
            $table->foreignId('update_by_service_hopi')->references('id')->on('users')->Ondelete('restrict');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('service__hopitals');
    }
};
