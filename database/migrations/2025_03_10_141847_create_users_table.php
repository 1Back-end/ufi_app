<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->string('login')->unique();
            $table->string('email')->nullable()->unique();
            $table->string('password');
            $table->string('nom_utilisateur');
            $table->string('prenom')->nullable();
            $table->integer('status')->default(1);
            $table->integer('connexion_counter')->default(0);
            $table->date('password_expiated_at')->nullable();
            $table->boolean('connected')->default(false);
            $table->boolean('default')->default(false)->comment("Indique si l'utilisateur a déjà changer son mot de passe par défaut !");
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
