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
            $table->integer('position')->default(0)->comment('Est la positions du préfixe dans le nom complet(0: aucun, 1: avant le 1er nom, 2: Après le 2e nom)');
            $table->integer('age_max')->nullable()->comment("Valeur max du préfixe.");
            $table->integer('age_min')->nullable()->comment("Valeur min du préfixe.");

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
