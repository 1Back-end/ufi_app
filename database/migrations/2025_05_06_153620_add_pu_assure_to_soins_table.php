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
        Schema::table('soins', function (Blueprint $table) {
            $table->integer('pu_default')->nullable()->after('pu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('soins', function (Blueprint $table) {
            $table->dropColumn('pu_default');
        });
    }
};
