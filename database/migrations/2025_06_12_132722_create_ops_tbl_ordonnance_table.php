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
        Schema::create('ops_tbl_ordonnance', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable(); // Code du rapport
            $table->foreignId('rapport_consultations_id')->nullable()->constrained('ops_tbl_rapport_consultations')->restrictOnDelete();
            $table->text('description')->nullable(); // Conseils ou prescriptions
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
        Schema::dropIfExists('ops_tbl_ordonnance');
    }
};
