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
        Schema::table('prestationables', function (Blueprint $table) {
            $table->decimal('consultant_amount', 15, 2)
                ->default(0)
                ->after('amount_contested');

            $table->string('consultant_amount_status')
                ->default('pending')
                ->after('consultant_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestationables', function (Blueprint $table) {
            $table->dropColumn([
                'consultant_amount',
                'consultant_status'
            ]);
        });
    }
};
