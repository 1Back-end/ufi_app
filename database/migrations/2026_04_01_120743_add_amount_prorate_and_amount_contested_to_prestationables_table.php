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
        Schema::table('prestationables', function (Blueprint $table) {
            $table->decimal('amount_prorate', 15, 2)->default(0)->after('pu')->comment('Montant proraté pour la prestation');
            $table->decimal('amount_contested', 15, 2)->default(0)->after('amount_prorate')->comment('Montant contesté pour la prestation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestationables', function (Blueprint $table) {
            $table->dropColumn(['amount_prorate', 'amount_contested']);
        });
    }
};
