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
        $indexes = collect(DB::select("SHOW INDEX FROM assureurs"))
            ->pluck('Key_name')
            ->toArray();

        Schema::table('assureurs', function (Blueprint $table) use ($indexes) {

            if (in_array('assureurs_tel_unique', $indexes)) {
                $table->dropUnique('assureurs_tel_unique');
            }

            if (in_array('assureurs_tel1_unique', $indexes)) {
                $table->dropUnique('assureurs_tel1_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assureurs', function (Blueprint $table) {
            $table->unique('tel', 'assureurs_tel_unique');
            $table->unique('tel1', 'assureurs_tel1_unique');
        });
    }
};
