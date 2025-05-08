<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('prise_en_charges', function (Blueprint $table) {
            $table->boolean('used')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('prise_en_charges', function (Blueprint $table) {
            $table->dropColumn('used');
        });
    }
};
