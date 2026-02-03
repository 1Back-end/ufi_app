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
        Schema::table('actes', function (Blueprint $table) {
            $table->foreignId('sub_act_category_id')->nullable()->constrained('sub_act_categories')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('actes', function (Blueprint $table) {
            $table->dropForeign(['sub_act_category_id']);
            $table->dropColumn('sub_act_category_id');
        });
    }
};
