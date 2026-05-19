<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. supprimer table si elle existe
        Schema::dropIfExists('consultant_disponibilites');

        // 2. recréer proprement
        Schema::create('consultant_disponibilites', function (Blueprint $table) {

            $table->id();

            $table->foreignId('consultant_id')
                ->constrained('consultants')
                ->cascadeOnDelete();

            $table->boolean('is_deleted')->default(false);

            $table->string('jour'); // ex: lundi, mardi...

            $table->time('heure');

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultant_disponibilites');
    }
};
