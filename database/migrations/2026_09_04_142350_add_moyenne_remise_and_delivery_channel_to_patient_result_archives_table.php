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
        Schema::table('patient_result_archives', function (Blueprint $table) {
            $table->unsignedBigInteger('delivery_channel_id')->nullable()->after('id');
            $table->foreign('delivery_channel_id')->references('id')->on('delivery_channels')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_result_archives', function (Blueprint $table) {
            $table->dropForeign(['delivery_channel_id']);
            $table->dropColumn('delivery_channel_id');
        });
    }
};
