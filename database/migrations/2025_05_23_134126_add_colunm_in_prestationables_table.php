<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('prestationables', function (Blueprint $table) {
            $table->decimal('pu')->after('amount_regulate')->nullable();
            $table->decimal('b')->after('pu')->nullable();
            $table->decimal('k_modulateur')->after('b')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('prestationables', function (Blueprint $table) {
            $table->dropColumn('pu');
            $table->dropColumn('b');
            $table->dropColumn('k_modulateur');
        });
    }
};
