<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultant_prestation_shares', function (Blueprint $table) {

            // 🔥 1. DROP FOREIGN KEYS D'ABORD
            $table->dropForeign(['consultant_id']);
            $table->dropForeign(['prestation_type_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });

        // 🔥 2. DROP INDEX UNIQUE
        DB::statement("
            ALTER TABLE consultant_prestation_shares
            DROP INDEX consultant_prestation_unique
        ");

        Schema::table('consultant_prestation_shares', function (Blueprint $table) {

            // 🔁 remettre les FK proprement
            $table->foreign('consultant_id')
                ->references('id')->on('consultants')
                ->onDelete('cascade');

            $table->foreign('prestation_type_id')
                ->references('id')->on('type_prestations')
                ->onDelete('cascade');

            $table->foreign('created_by')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->foreign('updated_by')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('consultant_prestation_shares', function (Blueprint $table) {

            $table->unique(
                ['consultant_id', 'prestation_type_id'],
                'consultant_prestation_unique'
            );
        });
    }
};
