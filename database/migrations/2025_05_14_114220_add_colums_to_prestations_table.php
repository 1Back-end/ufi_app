<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('prestations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payable_by');
            $table->foreignId('convention_associe_id')->nullable()
                ->after('prise_charge_id')
                ->references('id')->on('convention_associes')
                ->nullOnDelete();
        });

        Schema::table('factures', function (Blueprint $table) {
            $table->integer('amount_convention')->after('amount_client')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('prestations', function (Blueprint $table) {
            $table->foreignId('payable_by')->nullable()->after('prise_charge_id')->constrained('clients')->references('id')->restrictOnDelete();
            $table->dropConstrainedForeignId('convention_associe_id');
        });

        Schema::table('factures', function (Blueprint $table) {
            $table->dropColumn('amount_convention');
        });
    }
};
