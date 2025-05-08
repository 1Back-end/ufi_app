<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('prestationables', function (Blueprint $table) {
            $table->integer('amount_regulate')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('prestationables', function (Blueprint $table) {
            $table->dropColumn('amount_regulate');
        });
    }
};
