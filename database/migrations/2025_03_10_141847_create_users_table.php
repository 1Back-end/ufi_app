<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles');
            $table->string('nom_utilisateur');
            $table->string('mot_de_passe');
            $table->string('date_expiration_mot_passe');
            $table->string('email_utilisateur');
            $table->string('status_utilisateur');
            $table->string('date_creation_utilisateur');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
