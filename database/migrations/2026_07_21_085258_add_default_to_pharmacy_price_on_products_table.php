<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        DB::table('products')->whereNull('purchase_price')->update(['purchase_price' => 0]);
        DB::table('products')->whereNull('pharmacy_price')->update(['pharmacy_price' => 0]);


        Schema::table('products', function (Blueprint $table) {
            $table->integer('pharmacy_price')->default(0)->change();
            $table->integer('purchase_price')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('pharmacy_price')->nullable()->change();
            $table->integer('purchase_price')->nullable()->change();
        });
    }
};
