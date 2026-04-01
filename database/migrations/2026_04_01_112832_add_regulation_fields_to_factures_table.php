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
        Schema::table('factures', function (Blueprint $table) {
            $table->decimal('amount_prorate', 15, 2)->nullable()->after('amount_pc'); // Montant Proraté
            $table->decimal('amount_contested', 15, 2)->nullable()->after('amount_prorate'); // Montant contesté
            $table->decimal('amount_paid', 15, 2)->nullable()->after('amount_contested'); // Montant payé
            $table->decimal('amount_ir', 15, 2)->nullable()->after('amount_paid'); // Retenu IR
            $table->decimal('amount_received', 15, 2)->nullable()->after('amount_ir'); // Montant perçu
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            $table->dropColumn([
                'amount_prorate',
                'amount_contested',
                'amount_paid',
                'amount_ir',
                'amount_received',
            ]);
        });
    }
};
