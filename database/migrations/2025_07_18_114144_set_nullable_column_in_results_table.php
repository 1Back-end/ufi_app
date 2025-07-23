<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->string('result_client')->nullable()->change();
            $table->string('result_machine')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->string('result_client')->change();
            $table->string('result_machine')->change();
        });
    }
};
