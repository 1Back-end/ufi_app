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
        Schema::rename('rapports_actes', 'consultation_report_actes');

        Schema::table('consultation_report_actes', function (Blueprint $table) {
            if (!Schema::hasColumn('consultation_report_actes', 'code')) {
                $table->string('code')->nullable()->after('id');
            }

            if (!Schema::hasColumn('consultation_report_actes', 'description')) {
                $table->text('description')->nullable();
            }

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultation_report_actes', function (Blueprint $table) {
            $table->dropColumn(['code', 'description']);
            $table->dropSoftDeletes();
        });

        Schema::rename('consultation_report_actes', 'rapports_actes');
    }
};
