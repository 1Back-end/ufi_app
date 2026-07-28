<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transferts_stocks', function (Blueprint $table) {
            $table->foreignId('validated_by')
                ->nullable()
                ->after('updated_by')
                ->constrained('users')
                ->nullOnDelete();


            $table->timestamp('validated_at')
                ->nullable()
                ->after('validated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transferts_stocks', function (Blueprint $table) {
            $table->dropForeign(['validated_by']);
            $table->dropColumn(['validated_by', 'validated_at']);
        });
    }
};
