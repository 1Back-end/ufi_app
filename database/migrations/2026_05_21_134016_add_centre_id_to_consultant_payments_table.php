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
        Schema::table('consultant_payments', function (Blueprint $table) {
            $table->foreignId('centre_id')
                ->nullable()
                ->after('caisse_id')
                ->constrained('centres')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultant_payments', function (Blueprint $table) {
            $table->dropForeign(['centre_id']);
            $table->dropColumn('centre_id');
        });
    }
};
