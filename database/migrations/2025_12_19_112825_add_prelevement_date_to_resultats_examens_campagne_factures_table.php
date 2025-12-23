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
        Schema::table('resultats_examens_campagne_factures', function (Blueprint $table) {
            $table->date('prelevement_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resultats_examens_campagne_factures', function (Blueprint $table) {
            $table->dropColumn('prelevement_date');
        });
    }
};
