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
        Schema::table('ops_tbl_hospitalisation', function (Blueprint $table) {
            $table->integer('pu_default')->nullable()->after('pu'); // ou après le champ approprié
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ops_tbl_hospitalisation', function (Blueprint $table) {
            $table->dropColumn('pu_default');
        });
    }
};
