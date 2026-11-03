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
        Schema::table('rendez_vouses', function (Blueprint $table) {
            $table->string('type')->default('unbilled')->change();
        });

        DB::table('rendez_vouses')
            ->where('type', 'Non facturé')
            ->update(['type' => 'unbilled']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('rendez_vouses')
            ->where('type', 'unbilled')
            ->update(['type' => 'Non facturé']);

        Schema::table('rendez_vouses', function (Blueprint $table) {
            $table->string('type')->default(null)->change();
        });
    }
};
