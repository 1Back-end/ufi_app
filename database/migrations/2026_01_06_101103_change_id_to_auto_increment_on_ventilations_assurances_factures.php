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
            $table->dropPrimary(['id']);
            $table->dropColumn('id');

            // Ajouter un nouvel id auto-increment
            $table->bigIncrements('id')->first();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventilations_assurances_factures', function (Blueprint $table) {
            $table->uuid('id')->primary()->first();
        });
    }
};
