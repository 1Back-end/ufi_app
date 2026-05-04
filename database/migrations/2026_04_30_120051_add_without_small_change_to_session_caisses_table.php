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
            $table->integer('sold_without_small_change')
                ->default(0)
                ->after('current_sold');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_caisse', function (Blueprint $table) {
            $table->dropColumn('sold_without_small_change');
        });
    }
};
