<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('prestations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->unsignedBigInteger('prise_charge_id')->nullable();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('consultant_id');
            $table->unsignedBigInteger('centre_id');
            $table->unsignedBigInteger('payable_by')->nullable();
            $table->timestamp('programmation_date')->comment('Date de programmation de la prestation');
            $table->boolean('state')->default(true);
            $table->integer('type');
            $table->boolean('regulated')->default(false);

            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();
            $table->foreign('consultant_id')->references('id')->on('consultants')->restrictOnDelete();
            $table->foreign('centre_id')->references('id')->on('centres')->restrictOnDelete();
            $table->foreign('payable_by')->references('id')->on('clients')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('prise_charge_id')->references('id')->on('prise_en_charges')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestations');
    }
};
