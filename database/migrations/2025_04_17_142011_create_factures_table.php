<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sequence')->comment("Suivre ma séquence de la facture et de la proforma !");
            $table->unsignedBigInteger('centre_id')->comment("Facture est liée à un centre !");
            $table->string('code')->nullable();
            $table->unsignedBigInteger('prestation_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->dateTime('date_fact');
            $table->integer('amount');
            $table->integer('amount_pc');
            $table->integer('amount_remise');
            $table->integer('amount_client');
            $table->integer('type');
            $table->timestamps();

            $table->foreign('prestation_id')->references('id')->on('prestations')->restrictOnDelete();
            $table->foreign('centre_id')->references('id')->on('centres')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};
