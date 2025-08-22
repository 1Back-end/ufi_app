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
        Schema::create('examens_actes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rapport_consultation_id') ->nullable()->constrained('ops_tbl_rapport_consultations')->cascadeOnDelete();
            $table->foreignId('examen_id') ->nullable()->constrained('examens')->cascadeOnDelete();
            $table->string('name')->nullable(); // nom de l’acte
            $table->string('type')->nullable(); // Nouveau champ
            $table->text('description')->nullable(); // détails facultatifs
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('examens_actes');
    }
};
