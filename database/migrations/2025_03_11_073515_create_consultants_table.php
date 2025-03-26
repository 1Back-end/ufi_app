<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('consultants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->default(null)->references('id')->on('users');
            $table->foreignId('code_hopi')->references('id')->on('hopitals')->Ondelete('cascade');
            $table->foreignId('code_service_hopi')->references('id')->on('service__hopitals');
            $table->foreignId('code_specialite')->references('id')->on('specialites');
            $table->foreignId('code_titre')->references('id')->on('titres');
            $table->string('ref_consult');
            $table->string('nom_consult');
            $table->string('prenom_consult')->nullable();
            $table->string('nomcomplet_consult');
            $table->string('tel_consult');
            $table->string('tel1_consult');
            $table->string('email_consul');
            $table->string('type_consult');
            $table->string('status_consult')->default('Actif');
            $table->foreignId('create_by_consult')->nullable()->default(null)->references('id')->on('users');
            $table->foreignId('update_by_consult')->nullable()->default(null)->references('id')->on('users');
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
