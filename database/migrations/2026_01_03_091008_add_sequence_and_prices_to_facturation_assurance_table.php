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
        Schema::table('facturation_assurance', function (Blueprint $table) {
            $table->integer('sequence')->after('facture_number');
            $table->integer('price_after_application_hr')->after('amount');
            $table->integer('price_after_application_tva')->after('price_after_application_hr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturation_assurance', function (Blueprint $table) {
            $table->dropColumn([
                'sequence',
                'price_after_application_hr',
                'price_after_application_tva',
            ]);
        });
    }
};
