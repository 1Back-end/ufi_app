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
        Schema::table('session_element', function (Blueprint $table) {
            $table->foreignId('regulation_method_id')
                ->nullable()
                ->after('montant') // place la colonne après 'montant'
                ->constrained('regulation_methods') // table de référence
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_element', function (Blueprint $table) {
            $table->dropForeign(['regulation_method_id']);
            $table->dropColumn('regulation_method_id');
        });
    }
};
