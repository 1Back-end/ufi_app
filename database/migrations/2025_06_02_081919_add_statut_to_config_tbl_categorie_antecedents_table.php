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
        Schema::table('config_tbl_categorie_antecedents', function (Blueprint $table) {
            $table->string('status')->default('actif')->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('config_tbl_categorie_antecedents', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
