<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('centres', function (Blueprint $table) {
            $table->id();
            $table->string('nom_centre');
            $table->string('tel_centre');
            $table->string('numero_contribuable_centre');
            $table->string('registre_com_centre');
            $table->string('fax_centre');
            $table->string('email_centre');
            $table->string('numero_autorisation_centre');
            $table->string('logo_centre');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('centres');
    }
};
