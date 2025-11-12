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
        Schema::create('patient_archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('dossier_id')->nullable()->constrained('rendez_vouses')->nullOnDelete();
            $table->unsignedInteger('number_order');
            $table->date('first_visit_at')->nullable();
            $table->date('last_visit_at')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
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
        Schema::dropIfExists('patient_archives');
    }
};
