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
        Schema::table('transfert_fonds_tampons', function (Blueprint $table) {
            $table->bigInteger('montant_send')->unsigned()->change()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfert_fonds_tampons', function (Blueprint $table) {
            $table->dropColumn('montant_send');
        });
    }
};
