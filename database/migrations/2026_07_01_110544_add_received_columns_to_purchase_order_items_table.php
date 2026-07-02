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
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->integer('already_received_quantity')
                ->default(0)
                ->after('quantity')
                ->comment('Quantité déjà reçue lors des livraisons précédentes');

            $table->integer('remaining_quantity')
                ->default(0)
                ->after('already_received_quantity')
                ->comment('Le reste à recevoir pour cet article');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn(['already_received_quantity', 'remaining_quantity']);
        });
    }
};
