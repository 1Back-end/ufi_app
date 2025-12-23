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
        Schema::table('resultats_examens_campagne_factures', function (Blueprint $table) {
            $table->string('reference')->nullable()->unique()->after('id'); // Ajoute la colonne 'reference' après l'id
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resultats_examens_campagne_factures', function (Blueprint $table) {
            $table->dropColumn('reference');
        });
    }
};
