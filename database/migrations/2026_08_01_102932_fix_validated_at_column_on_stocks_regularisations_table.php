<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks_regularisations', function (Blueprint $table) {
            if (Schema::hasColumn('stocks_regularisations', 'validated_at')) {
                $table->dropColumn('validated_at');
            }
        });

        Schema::table('stocks_regularisations', function (Blueprint $table) {
            $table->timestamp('validated_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('stocks_regularisations', function (Blueprint $table) {
            if (Schema::hasColumn('stocks_regularisations', 'validated_at')) {
                $table->dropColumn('validated_at');
            }
        });
    }
};
