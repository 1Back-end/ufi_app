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
            $table->string('generic_name')->nullable()->after('name');
            $table->string('manufacturer_reference')->nullable()->after('generic_name');

            $table->string('product_type')->after('manufacturer_reference');

            $table->string('laboratory_family')->nullable()->after('product_type');
            $table->string('storage_unit')->nullable()->after('laboratory_family');
            $table->string('consumption_unit')->nullable()->after('storage_unit');

            $table->integer('conversion_factor')->nullable()->after('consumption_unit');
            $table->integer('alert_threshold')->nullable()->after('conversion_factor');
            $table->integer('minimum_threshold')->nullable()->after('alert_threshold');

            $table->string('storage_temperature')->nullable()->after('minimum_threshold');
            $table->integer('purchase_price')->nullable()->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'generic_name',
                'manufacturer_reference',
                'product_type',
                'laboratory_family',
                'storage_unit',
                'consumption_unit',
                'conversion_factor',
                'alert_threshold',
                'minimum_threshold',
                'storage_temperature',
                'purchase_price'
            ]);
        });
    }
};
