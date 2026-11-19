<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prestations', function (Blueprint $table) {
            $table->foreignId('recommendated_by_me')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('prestations', function (Blueprint $table) {
            $table->dropForeign(['recommendated_by_me']);
            $table->dropColumn('recommendated_by_me');
        });
    }
};
