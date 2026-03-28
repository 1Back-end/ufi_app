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
        Schema::table('session_caisse', function (Blueprint $table) {
            $table->dropForeign(['session_id']);

            // 🔹 Supprimer la colonne
            $table->dropColumn('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_caisse', function (Blueprint $table) {
            $table->foreignId('session_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });
    }
};
