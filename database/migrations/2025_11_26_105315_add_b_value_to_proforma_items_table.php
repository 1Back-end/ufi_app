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
        Schema::table('proforma_items', function (Blueprint $table) {
            $table->integer('b_value')->nullable()->after('kb_prelevement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proforma_items', function (Blueprint $table) {
            $table->dropColumn('b_value');
        });
    }
};
