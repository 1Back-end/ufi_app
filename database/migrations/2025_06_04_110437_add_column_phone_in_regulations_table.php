<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('regulations', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('type');
            $table->string('reference')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('regulations', function (Blueprint $table) {
            $table->dropColumn(['phone', 'reference']);
        });
    }
};
