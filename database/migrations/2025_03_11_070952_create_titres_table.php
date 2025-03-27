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
            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->boolean('is_deleted')->default(false); // Add the is_deleted column
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('titres');
    }
};
