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
        Schema::create('ops_tbl_rapport_consultations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable(); // Code du rapport
            $table->text('conclusion')->nullable(); // Résumé ou conclusion médicale
            $table->text('recommandations')->nullable(); // Conseils ou prescriptions
            $table->foreignId('motif_consultation_id')->nullable()->constrained('ops_tbl__motif_consultations')->restrictOnDelete();
            $table->boolean('is_deleted')->default(false);
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
        Schema::dropIfExists('ops_tbl_rapport_consultations');
    }
};
