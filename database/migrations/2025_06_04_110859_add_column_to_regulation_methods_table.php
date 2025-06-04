<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('regulation_methods', function (Blueprint $table) {
            $table->boolean('phone_method')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('regulation_methods', function (Blueprint $table) {
            $table->dropColumn('phone_method');
        });
    }
};
