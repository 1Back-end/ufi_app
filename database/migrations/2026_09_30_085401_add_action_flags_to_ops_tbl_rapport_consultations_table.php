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
        Schema::table('ops_tbl_rapport_consultations', function (Blueprint $table) {
            $table->boolean('can_add_examens')->default(false);
            $table->boolean('can_add_actes')->default(false);
            $table->boolean('can_add_diagnostic')->default(false);
            $table->boolean('can_add_ordonnance')->default(false);
            $table->boolean('can_add_certificat_medical')->default(false);
            $table->boolean('can_add_mise_en_observation')->default(false);
            $table->boolean('can_add_referre_medical')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ops_tbl_rapport_consultations', function (Blueprint $table) {
            $table->dropColumn([
                'can_add_examens',
                'can_add_actes',
                'can_add_diagnostic',
                'can_add_ordonnance',
                'can_add_certificat_medical',
                'can_add_mise_en_observation',
                'can_add_referre_medical',
            ]);
        });
    }
};
