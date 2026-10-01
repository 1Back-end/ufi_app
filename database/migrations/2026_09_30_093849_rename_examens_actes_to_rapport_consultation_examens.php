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
        Schema::rename('examens_actes', 'consultation_report_exams');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('consultation_report_exams', 'examens_actes');
    }
};
