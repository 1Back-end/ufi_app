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
        Schema::table('session_element', function (Blueprint $table) {
            $table->unsignedBigInteger('prestation_id')->nullable()->after('id');

            $table->foreign('prestation_id')
                ->references('id')
                ->on('prestations')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_element', function (Blueprint $table) {
            $table->dropForeign(['prestation_id']);
            $table->dropColumn('prestation_id');
        });
    }
};
