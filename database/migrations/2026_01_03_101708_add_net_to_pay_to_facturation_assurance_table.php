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
            $table->decimal('net_to_pay', 15, 2)->default(0)->after('price_after_application_tva');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturation_assurance', function (Blueprint $table) {
            $table->dropColumn('net_to_pay');
        });
    }
};
