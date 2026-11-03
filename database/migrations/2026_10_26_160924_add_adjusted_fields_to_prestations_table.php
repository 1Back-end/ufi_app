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
        try {
            DB::statement('ALTER TABLE prestations DROP FOREIGN KEY prestations_adjusted_by_foreign');
        } catch (\Exception $e) {
        }
        try {
            DB::statement('ALTER TABLE prestations DROP COLUMN adjusted_by');
        } catch (\Exception $e) {
        }
        try {
            DB::statement('ALTER TABLE prestations DROP COLUMN adjusted_at');
        } catch (\Exception $e) {
        }
        Schema::table('prestations', function (Blueprint $table) {
            $table->timestamp('adjusted_at')->nullable();
            $table->foreignId('adjusted_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE prestations DROP FOREIGN KEY prestations_adjusted_by_foreign');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE prestations DROP COLUMN adjusted_by');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE prestations DROP COLUMN adjusted_at');
        } catch (\Exception $e) {}
    }
};
