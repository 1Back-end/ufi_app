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
        Schema::table('consultant_prestation_shares', function (Blueprint $table) {
            $table->boolean('apply_on_care')->default(false)->after('is_active');
            $table->boolean('apply_on_clients')->default(false)->after('apply_on_care');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultant_prestation_shares', function (Blueprint $table) {
            $table->dropColumn(['apply_on_care', 'apply_on_clients']);
        });
    }
};
