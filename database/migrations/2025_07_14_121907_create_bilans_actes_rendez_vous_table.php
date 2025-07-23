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
        Schema::create('bilans_actes_rendez_vous', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rendez_vous_id')
                ->constrained('rendez_vouses')
                ->onDelete('cascade');

            $table->foreignId('acte_id')
                ->constrained('actes')
                ->onDelete('cascade');

            $table->text('resume')->nullable();
            $table->text('conclusion')->nullable();
            $table->foreignId('created_by')->constrained('users')->references('id')->restrictOnDelete();
            $table->foreignId('updated_by')->constrained('users')->references('id')->restrictOnDelete();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bilans_actes_rendez_vous');
    }
};
