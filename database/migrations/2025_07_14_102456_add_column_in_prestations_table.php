<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('prestations', function (Blueprint $table) {
            $table->boolean('apply_prelevement')->default(false);
        });

        Schema::table('factures', function (Blueprint $table) {
            $table->integer('amount_prelevement')->after('amount_client')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('prestations', function (Blueprint $table) {
            $table->dropColumn('apply_prelevement');
        });

        Schema::table('factures', function (Blueprint $table) {
            $table->dropColumn('amount_prelevement');
        });
    }
};
