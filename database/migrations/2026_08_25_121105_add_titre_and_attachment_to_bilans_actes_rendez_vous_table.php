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
            $table->string('titre')->nullable()->after('prestation_id');
            $table->string('attachment')->nullable()->after('titre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bilans_actes_rendez_vous', function (Blueprint $table) {
            $table->dropColumn(['titre', 'attachment']);
        });
    }
};
