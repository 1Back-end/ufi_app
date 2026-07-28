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
            $table->foreignId('received_packaging_id')
                ->nullable()
                ->after('quantite_recue')
                ->constrained('packagings')
                ->nullOnDelete();

            $table->integer('unit_quantity_received')
                ->default(0)
                ->after('received_packaging_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approvisionnements', function (Blueprint $table) {
            $table->dropForeign(['received_packaging_id']);
            $table->dropColumn(['received_packaging_id', 'unit_quantity_received']);
        });
    }
};
