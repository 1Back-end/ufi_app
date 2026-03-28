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
            $table->timestamp('pause_ts')->nullable()->after('ouverture_ts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_caisse', function (Blueprint $table) {
            $table->dropColumn('pause_ts');
        });
    }
};
