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
        Schema::table('rendez_vouses', function (Blueprint $table) {
            $table->unsignedTinyInteger('etat_paiement')->default(0)->after('is_deleted')->comment('0: non payé, 1: moitié payé, 2: payé');
            $table->foreignId('facture_id')->nullable()->after('etat_paiement')->constrained('factures')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rendez_vouses', function (Blueprint $table) {
            $table->dropForeign(['facture_id']);
            $table->dropColumn(['etat_paiement', 'facture_id']);
        });
    }
};
