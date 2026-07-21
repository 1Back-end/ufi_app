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
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('allow_negative_stock')->default(false)->after('facturable');
            $table->boolean('has_expiration_date')->default(false)->after('allow_negative_stock');
            $table->boolean('has_moratorium')->default(false)->after('has_expiration_date');
            $table->integer('moratorium_months')->nullable()->after('has_moratorium');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'allow_negative_stock',
                'has_expiration_date',
                'has_moratorium',
                'moratorium_months',
            ]);
        });
    }
};
