<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('element_paillasses', function (Blueprint $table) {
            $table->foreignId('predefined_list_id')->nullable()->constrained('predefined_lists')->references('id')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('element_paillasses', function (Blueprint $table) {
            $table->dropForeign('predefined_list_id');
            $table->dropColumn('predefined_list_id');
        });
    }
};
