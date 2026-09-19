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
        Schema::table('rendez_vouses', function (Blueprint $table) {
            $table->boolean('imaging_results_delivered')->default(false)->after('id');
            $table->unsignedBigInteger('imaging_delivered_by_user_id')->nullable()->after('imaging_results_delivered');
            $table->foreign('imaging_delivered_by_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->boolean('nursing_results_delivered')->default(false)->after('imaging_delivered_by_user_id');
            $table->unsignedBigInteger('nursing_delivered_by_user_id')->nullable()->after('nursing_results_delivered');
            $table->foreign('nursing_delivered_by_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rendez_vouses', function (Blueprint $table) {
            $table->dropForeign(['imaging_delivered_by_user_id']);
            $table->dropForeign(['nursing_delivered_by_user_id']);

            $table->dropColumn([
                'imaging_results_delivered',
                'imaging_delivered_by_user_id',
                'nursing_results_delivered',
                'nursing_delivered_by_user_id'
            ]);
        });
    }
};
