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
        Schema::table('emplacements_products', function (Blueprint $table) {
            $table->boolean('is_primary')->default(false)->after('id')
                ->comment('Indique si cet emplacement est principal pour le produit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emplacements_products', function (Blueprint $table) {
            $table->dropColumn('is_primary');
        });
    }
};
