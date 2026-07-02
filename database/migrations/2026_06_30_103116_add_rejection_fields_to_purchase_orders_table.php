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
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->timestamp('rejected_at')->nullable()->after('status');
            $table->text('reason_of_rejection')->nullable()->after('rejected_at');
            $table->unsignedBigInteger('rejected_by')->nullable()->after('reason_of_rejection');

            $table->foreign('rejected_by')
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
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['rejected_by']);

            $table->dropColumn([
                'rejected_at',
                'reason_of_rejection',
                'rejected_by'
            ]);
        });
    }
};
