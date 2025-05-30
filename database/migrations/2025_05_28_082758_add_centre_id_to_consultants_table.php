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
        Schema::table('consultants', function (Blueprint $table) {
            $table->foreignId('centre_id')->nullable()->constrained('centres')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultants', function (Blueprint $table) {
            // Suppression de la clé étrangère et colonne en rollback
            $table->dropForeign(['centre_id']);
            $table->dropColumn('centre_id');
        });
    }
};
