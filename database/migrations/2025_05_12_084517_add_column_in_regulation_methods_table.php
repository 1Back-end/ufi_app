<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('regulation_methods', function (Blueprint $table) {
            $table->integer('type_regulation')->nullable()->after('comment_required');
        });
    }

    public function down(): void
    {
        Schema::table('regulation_methods', function (Blueprint $table) {
            $table->dropColumn("type_regulation");
        });
    }
};
