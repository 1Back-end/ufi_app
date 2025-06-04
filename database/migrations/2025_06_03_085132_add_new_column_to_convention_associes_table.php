<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('convention_associes', function (Blueprint $table) {
            $table->integer('amount')->after('amount_max')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('convention_associes', function (Blueprint $table) {
            $table->dropColumn('amount');
        });
    }
};
