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
            $table->integer('small_change')
                ->default(0)
                ->after('montant_send');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfert_fonds_tampons', function (Blueprint $table) {
            $table->dropColumn('small_change');
        });
    }
};
