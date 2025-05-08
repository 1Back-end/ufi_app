<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rendez_vouses', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('consultant_id')->constrained('consultants')->onDelete('cascade');

            $table->dateTime('date_emission');
            $table->dateTime('dateheure_rdv');

            $table->string('details');
            $table->integer('nombre_jour_validite');
            $table->string('type')->default('Facturé');
            $table->string('etat')->default('Actif');
            $table->boolean('is_deleted')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rendez_vouses');
    }
};
