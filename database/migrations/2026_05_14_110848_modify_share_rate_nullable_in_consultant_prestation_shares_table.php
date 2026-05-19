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
            $table->decimal('share_rate', 5, 2)->nullable()->change();
            $table->decimal('price', 12, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultant_prestation_shares', function (Blueprint $table) {
            $table->decimal('share_rate', 5, 2)->default(0.00)->nullable(false)->change();
            $table->decimal('price', 12, 2)->default(0.00)->nullable(false)->change();
        });
    }
};
