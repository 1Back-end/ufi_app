<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rendez_vouses', function (Blueprint $table) {
            $table->string('type')->default('billed')->change();
        });

        DB::table('rendez_vouses')
            ->where('type', 'Facturé')
            ->update(['type' => 'billed']);
    }

    public function down(): void
    {
        DB::table('rendez_vouses')
            ->where('type', 'billed')
            ->update(['type' => 'Facturé']);

        Schema::table('rendez_vouses', function (Blueprint $table) {
            $table->string('type')->default(null)->change();
        });
    }
};
