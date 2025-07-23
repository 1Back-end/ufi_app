<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('element_paillasses', function (Blueprint $table) {
            $table->integer('indent')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('element_paillasses', function (Blueprint $table) {
            $table->dropColumn('indent');
        });
    }
};
