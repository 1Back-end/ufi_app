<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_channels', function (Blueprint $table) {
            $table->boolean('is_patient_info')->default(false)->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_channels', function (Blueprint $table) {
            $table->dropColumn('is_patient_info');
        });
    }
};
