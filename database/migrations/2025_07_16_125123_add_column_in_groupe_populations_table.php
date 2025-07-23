<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('groupe_populations', function (Blueprint $table) {
            $table->foreignId('sex_id')->nullable()->constrained('sexes')->references('id')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('groupe_populations', function (Blueprint $table) {
            $table->dropForeign('sex_id');
            $table->dropColumn('sex_id');
        });
    }
};
