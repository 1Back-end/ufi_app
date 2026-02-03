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
        Schema::table('actes', function (Blueprint $table) {
            $table->integer('pu_assurance')->nullable()->after('pu');
            $table->string('code')->nullable()->after('pu_assurance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('actes', function (Blueprint $table) {
            $table->dropColumn(['pu_assurance', 'code']);
        });
    }
};
