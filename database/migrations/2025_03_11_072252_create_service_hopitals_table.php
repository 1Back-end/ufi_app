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
            $table->foreignId('create_by_service_hopi')->nullable()->default(null)->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('update_by_service_hopi')->nullable()->default(null)->references('id')->on('users')->onDelete('cascade');
            $table->boolean('is_deleted')->default(false); // Add the is_deleted column
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('service__hopitals');
    }
};
