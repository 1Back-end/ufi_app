<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('prefixes', function (Blueprint $table) {
            $table->id();
            $table->string('prefixe')->comment('"Enfant", "Epouse", "Bébé", "Sans prefixe". Enfant valide si age <18, Bébé<3 ans, Epouse si sexe = Feminin');
            $table->foreignId('create_by')->references('id')->on('users')->restrictOnDelete();;
            $table->foreignId('update_by')->references('id')->on('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prefixes');
    }
};
