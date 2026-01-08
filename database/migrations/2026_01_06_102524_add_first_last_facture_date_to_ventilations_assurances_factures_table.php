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
        Schema::table('ventilations_assurances_factures', function (Blueprint $table) {
            $table->date('first_facture_date')->nullable()->after('ventilation_date');
            $table->date('last_facture_date')->nullable()->after('first_facture_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventilations_assurances_factures', function (Blueprint $table) {
            $table->dropColumn(['first_facture_date', 'last_facture_date']);
        });
    }
};
