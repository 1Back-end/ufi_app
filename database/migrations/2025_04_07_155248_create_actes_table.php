<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('actes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->unsignedBigInteger('type_acte_id');
            $table->string('name');
            $table->integer('pu')->comment("Prix unitaire");
            $table->integer('delay');
            $table->integer('k_modulateur')->default(0);
            $table->integer('coefficient')->default(0);
            $table->integer('cotation')->default(0);
            $table->boolean('state')->default(1)->comment("0: Inactif, 1: Actif");
            $table->timestamps();
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('type_acte_id')->references('id')->on('type_actes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actes');
    }
};
