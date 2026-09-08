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
        Schema::table('config_tbl_categories_examen_physiques', function (Blueprint $table) {
            $table->integer('order')->default(0);
            $table->softDeletes();
            $table->string('code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('config_tbl_categories_examen_physiques', function (Blueprint $table) {
            $table->dropColumn(['order', 'code']);
            $table->dropSoftDeletes();
        });
    }
};
