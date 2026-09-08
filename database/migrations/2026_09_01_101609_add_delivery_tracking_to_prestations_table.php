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
            $table->timestamp('result_delivered_at')->nullable()->after('prelevate_at');
            $table->foreignId('delivered_by')->nullable()->constrained('users')->nullOnDelete()->after('result_delivered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestations', function (Blueprint $table) {
            $table->dropForeign(['delivered_by']);
            $table->dropColumn(['result_delivered_at', 'delivered_by']);
        });
    }
};
