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
        Schema::table('prise_en_charges', function (Blueprint $table) {
            $table->boolean('usage_unique')->default(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prise_en_charges', function (Blueprint $table) {
            $table->string('usage_unique')->default('Non')->change();
        });
    }
};
