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
        Schema::table('prestations', function (Blueprint $table) {
            $table->unsignedBigInteger('entered_by')->nullable()->after('id');
            $table->timestamp('entered_at')->nullable()->after('entered_by');

            $table->foreign('entered_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestations', function (Blueprint $table) {
            $table->dropForeign(['entered_by']);
            $table->dropColumn(['entered_by', 'entered_at']);
        });
    }
};
