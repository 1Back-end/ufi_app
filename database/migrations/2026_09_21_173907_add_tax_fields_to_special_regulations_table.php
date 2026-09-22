<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('special_regulations', function (Blueprint $table) {
            $table->boolean('apply_ir')->default(false)->after('amount_waiting');
            $table->decimal('ir_rate', 8, 2)->default(0)->after('apply_ir');
            $table->decimal('total_ir_amount', 15, 2)->default(0)->after('ir_rate');

            $table->boolean('apply_tva')->default(false)->after('total_ir_amount');
            $table->decimal('tva_rate', 8, 2)->default(0)->after('apply_tva');
            $table->decimal('total_tva_amount', 15, 2)->default(0)->after('tva_rate');

            $table->decimal('others_amount_excluded', 15, 2)->default(0)->after('total_tva_amount');
            $table->decimal('net_to_pay', 15, 2)->default(0)->after('others_amount_excluded');
        });
    }

    public function down(): void
    {
        Schema::table('special_regulations', function (Blueprint $table) {
            $table->dropColumn([
                'apply_ir',
                'ir_rate',
                'total_ir_amount',
                'apply_tva',
                'tva_rate',
                'total_tva_amount',
                'others_amount_excluded',
                'net_to_pay'
            ]);
        });
    }
};
