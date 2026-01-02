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
        Schema::table('campagne_factures', function (Blueprint $table) {
            $table->date('facturation_date')->nullable()->after('billing_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campagne_factures', function (Blueprint $table) {
            $table->dropColumn('facturation_date');
        });
    }
};
