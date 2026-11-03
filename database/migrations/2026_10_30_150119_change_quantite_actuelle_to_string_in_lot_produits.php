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
        Schema::table('lot_produits', function (Blueprint $table) {
            $table->string('quantite_actuelle')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lot_produits', function (Blueprint $table) {
            $table->integer('quantite_actuelle')->default(0)->change();
        });
    }
};
