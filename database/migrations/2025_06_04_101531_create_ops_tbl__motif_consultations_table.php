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
        Schema::create('ops_tbl__motif_consultations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->foreignId('dossier_consultation_id')->nullable()->constrained('dossier_consultations')->nullOnDelete();
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
        Schema::dropIfExists('ops_tbl__motif_consultations');
    }
};
