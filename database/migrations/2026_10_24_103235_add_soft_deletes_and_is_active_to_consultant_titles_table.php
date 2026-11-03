<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécute les migrations.
     */
    public function up(): void
    {
        Schema::table('consultant_titles', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('id');
            $table->softDeletes();
        });
    }

    /**
     * Annule les migrations.
     */
    public function down(): void
    {
        Schema::table('consultant_titles', function (Blueprint $table) {
            $table->dropColumn('is_active');
            $table->dropSoftDeletes();
        });
    }
};
