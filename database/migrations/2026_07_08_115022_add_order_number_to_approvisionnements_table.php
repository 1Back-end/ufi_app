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
        Schema::table('approvisionnements', function (Blueprint $table) {
            $table->string('order_number')
                ->nullable()
                ->after('id')
                ->comment('Numéro de bon de livraison associé à l\'approvisionnement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approvisionnements', function (Blueprint $table) {
            $table->dropColumn('order_number');
        });
    }
};
