<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('price')->default(0)->nullable()->change();
            $table->integer('purchase_price')->default(0)->nullable()->change();
            $table->integer('pharmacy_price')->default(0)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('price')->nullable(false)->change();
            $table->integer('purchase_price')->nullable(false)->change();
            $table->integer('pharmacy_price')->nullable(false)->change();
        });
    }
};
