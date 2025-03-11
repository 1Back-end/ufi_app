<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles')->onDelete('cascade');
            $table->string('nom_utilisateur');
            $table->string('password');
            $table->date('date_expiration_mot_passe'); // Correction : Utilisation de 'date' au lieu de 'string'
            $table->string('email')->unique(); // Correction : Ajout de 'unique'
            $table->string('status_utilisateur');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
