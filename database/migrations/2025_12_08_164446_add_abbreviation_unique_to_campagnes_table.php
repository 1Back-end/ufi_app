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
        Schema::table('campagnes', function (Blueprint $table) {
            if (!Schema::hasColumn('campagnes', 'abbreviation_unique')) {
                $table->string('abbreviation_unique')->unique()->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campagnes', function (Blueprint $table) {
            if (Schema::hasColumn('campagnes', 'abbreviation_unique')) {
                $table->dropColumn('abbreviation_unique');
            }
        });
    }
};
