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
        Schema::table('transfert_fonds_tampons', function (Blueprint $table) {
            $table->text('reason_of_transfer')->nullable()->after('type');
            $table->timestamp('transfer_date')->nullable()->after('reason');
            $table->unsignedBigInteger('transferred_by')->nullable()->after('transfer_date');
            $table->foreign('transferred_by')
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
        Schema::table('transfert_fonds_tampons', function (Blueprint $table) {
            $table->dropForeign(['transferred_by']);
            $table->dropColumn(['reason_of_transfer', 'transfer_date', 'transferred_by']);
        });
    }
};
