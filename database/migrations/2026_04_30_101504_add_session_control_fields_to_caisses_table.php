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
        Schema::table('caisses', function (Blueprint $table) {
            $table->timestamp('session_control_expires_at')->nullable();
            $table->enum('session_control_status', ['active', 'blocked'])->default('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caisses', function (Blueprint $table) {
            $table->dropColumn('session_control_expires_at');
            $table->enum('session_control_status', ['active', 'blocked'])->default('active');
        });
    }
};
