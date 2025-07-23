<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('type_results', function (Blueprint $table) {
            $table->string('type')->nullable();
            $table->foreignId('cat_predefined_list_id')->nullable()->constrained('cat_predefined_lists')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('type_results', function (Blueprint $table) {
            $table->dropForeign(['cat_predefined_list_id']);
            $table->dropColumn(['type', 'cat_predefined_list_id']);
        });
    }
};
