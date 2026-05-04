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
        Schema::table('special_regulations', function (Blueprint $table) {
            $table->decimal('amount_waiting', 15, 2)
                ->default(0)
                ->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('special_regulations', function (Blueprint $table) {
            $table->dropColumn('amount_waiting');
        });
    }
};
