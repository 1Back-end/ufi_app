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
        Schema::table('bilans_actes_rendez_vous', function (Blueprint $table) {
            if (!Schema::hasColumn('bilans_actes_rendez_vous', 'consultant_id')) {
                $table->foreignId('consultant_id')->nullable()->after('prestation_id')->constrained('consultants')->onDelete('restrict');
            }
            if (!Schema::hasColumn('bilans_actes_rendez_vous', 'technique_analyse_id')) {
                $table->foreignId('technique_analyse_id')->nullable()->after('consultant_id')->constrained('analysis_techniques')->onDelete('restrict');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bilans_actes_rendez_vous', function (Blueprint $table) {
            $table->dropForeign(['consultant_id']);
            $table->dropColumn('consultant_id');

            $table->dropForeign(['technique_analyse_id']);
            $table->dropColumn('technique_analyse_id');
        });
    }
};
