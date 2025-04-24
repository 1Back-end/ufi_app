<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('centres', function (Blueprint $table) {
            $table->json('horaires')->nullable()->after('website');
            $table->string('postal_code')->nullable()->after('address');
            $table->boolean('active')->default(true)->after('postal_code');
        });
    }

    public function down(): void
    {
        Schema::table('centres', function (Blueprint $table) {
            $table->dropColumn(['horaires', 'postal_code', 'active']);
        });
    }
};
