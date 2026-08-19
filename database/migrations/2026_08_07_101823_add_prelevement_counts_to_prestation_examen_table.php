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
        Schema::table('prestationables', function (Blueprint $table) {
            $table->boolean('is_preleve')->default(false)->after('prelevements');
            $table->unsignedInteger('prelevement_count')->default(0)->after('is_preleve');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestationables', function (Blueprint $table) {
            $table->dropColumn(['is_preleve', 'prelevement_count']);
        });
    }
};
