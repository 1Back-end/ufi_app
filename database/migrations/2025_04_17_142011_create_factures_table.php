<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->nullable();
            $table->unsignedBigInteger('prestation_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->dateTime('date_fact')->nullable();
            $table->integer('amount');
            $table->integer('amount_pc');
            $table->integer('amount_remise');
            $table->integer('type');
            $table->timestamps();

            $table->foreign('prestation_id')->references('id')->on('prestations')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};
