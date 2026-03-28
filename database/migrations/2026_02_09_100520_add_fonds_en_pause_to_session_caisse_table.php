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
        Schema::table('session_caisse', function (Blueprint $table) {
            $table->decimal('fonds_en_pause', 15, 2)->nullable()->after('fonds_fermeture')->comment('Solde de la session au moment de la pause');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_caisse', function (Blueprint $table) {
            $table->dropColumn('fonds_en_pause');
        });
    }
};
