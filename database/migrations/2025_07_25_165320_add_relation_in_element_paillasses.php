<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('element_paillasses', function (Blueprint $table) {
            $table->foreignId('element_paillasses_id')->nullable()->constrained('element_paillasses')->references('id')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('element_paillasses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('element_paillasses_id');
            $table->dropColumn('element_paillasses_id');
        });
    }
};
