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
            $table->dropForeign(['user_manager_id']);
            $table->dropColumn(['amount', 'user_manager_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caisses', function (Blueprint $table) {
            $table->integer('amount')->default(0)->after('name');
            $table->foreignId('user_manager_id')
                ->after('amount')
                ->constrained('users')
                ->onDelete('cascade');
        });
    }
};
