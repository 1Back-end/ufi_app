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
        Schema::table('campagnes', function (Blueprint $table) {
            // Si 'reference' existe déjà, on ne le recrée pas
            if (!Schema::hasColumn('campagnes', 'full_name')) {
                $table->string('full_name')->nullable()->after('title'); // nom complet
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campagnes', function (Blueprint $table) {
            if (Schema::hasColumn('campagnes', 'full_name')) {
                $table->dropColumn('full_name');
            }
        });
    }
};
