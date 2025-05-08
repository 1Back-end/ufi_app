<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('consultants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('code_hopi')->references('id')->on('hopitals')->Ondelete('cascade');
            $table->foreignId('code_service_hopi')->references('id')->on('service__hopitals');
            $table->foreignId('code_specialite')->references('id')->on('specialites');
            $table->foreignId('code_titre')->references('id')->on('titres');
            $table->string('ref');
            $table->string('nom');
            $table->string('prenom')->nullable();
            $table->string('nomcomplet');
            $table->string('tel');
            $table->string('tel1');
            $table->string('email');
            $table->string('type');
            $table->string('status')->default('Actif');
            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->string('TelWhatsApp')->nullable()->default('Non');
            $table->boolean('is_deleted')->default(false); // Add the is_deleted column
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('consultants');
    }
};
