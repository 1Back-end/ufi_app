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
            $table->foreignId('caisse_depart_id')->nullable()->change();
            $table->foreignId('caisse_reception_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfert_fonds_tampons', function (Blueprint $table) {
            $table->foreignId('caisse_depart_id')->nullable(false)->change();
            $table->foreignId('caisse_reception_id')->nullable(false)->change();
        });
    }
};
