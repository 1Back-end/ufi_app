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
        Schema::table('patient_archives', function (Blueprint $table) {
            $table->timestamp('first_visit_at')->nullable()->change();
            $table->timestamp('last_visit_at')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_archives', function (Blueprint $table) {
            $table->dateTime('first_visit_at')->nullable()->change();
            $table->dateTime('last_visit_at')->nullable()->change();
        });
    }
};
