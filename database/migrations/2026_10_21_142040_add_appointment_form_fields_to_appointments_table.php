<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'appointment_date')) {
                $table->date('appointment_date')->nullable();
            }
            if (!Schema::hasColumn('appointments', 'appointment_time')) {
                $table->time('appointment_time')->nullable();
            }
            if (!Schema::hasColumn('appointments', 'day_of_week')) {
                $table->string('day_of_week')->nullable();
            }
            if (!Schema::hasColumn('appointments', 'appointment_type')) {
                $table->string('appointment_type')->default('on_demand');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'appointment_date',
                'appointment_time',
                'day_of_week',
                'appointment_type'
            ]);
        });
    }
};
