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
            $table->enum('position', ['open', 'close', 'in_pause'])
                ->default('close')
                ->after('amount');

            $table->boolean('is_primary')
                ->default(false)
                ->after('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caisses', function (Blueprint $table) {
            $table->dropColumn(['position', 'is_primary']);
        });
    }
};
