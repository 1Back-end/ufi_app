<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prestationables', function (Blueprint $table) {
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('printed_at')->nullable();

            // Si la table des utilisateurs s'appelle bien 'users'
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('printed_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('prestationables', function (Blueprint $table) {
            $table->dropForeign(['validated_by']);
            $table->dropForeign(['printed_by']);
            $table->dropColumn(['validated_at', 'printed_at', 'validated_by', 'printed_by']);
        });
    }
};
