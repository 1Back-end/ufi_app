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
        if (!Schema::hasColumn('product_types', 'centre_id')) {
            Schema::table('product_types', function (Blueprint $table) {
                $table->foreignId('centre_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('centres')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_types', function (Blueprint $table) {
            if (Schema::hasColumn('product_types', 'centre_id')) {
                $table->dropForeign(['centre_id']);
                $table->dropColumn('centre_id');
            }
        });
    }
};
