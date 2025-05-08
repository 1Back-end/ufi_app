<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('regulations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('regulation_method_id');
            $table->unsignedBigInteger('facture_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->integer('amount');
            $table->dateTime('date');
            $table->integer('type');
            $table->string('comment')->nullable();
            $table->string('reason')->nullable();
            $table->integer('state')->default(1);
            $table->boolean('particular')->default(false)->comment('Indique si la regulation est pour un particulier');
            $table->timestamps();

            $table->foreign('regulation_method_id')->references('id')->on('regulation_methods');
            $table->foreign('facture_id')->references('id')->on('factures');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::table('factures', function (Blueprint $table) {
            $table->integer('state')->default(1)->after('type')->comment("Etat de la facture: 1: Créer, 2: En cours, 3: Soldé, 4: Annulé");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regulations');
        Schema::table('factures', function (Blueprint $table) {
            $table->dropColumn('state');
        });
    }
};
