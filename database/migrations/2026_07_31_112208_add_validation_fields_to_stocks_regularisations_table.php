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
        Schema::table('stocks_regularisations', function (Blueprint $table) {
            // Suppression sécurisée de la clé étrangère si elle existe déjà
            if (Schema::hasColumn('stocks_regularisations', 'validated_by')) {
                $table->dropForeign(['validated_by']);
            }

            // Suppression sécurisée des colonnes si elles existent déjà
            $columnsToDrop = [];
            if (Schema::hasColumn('stocks_regularisations', 'validated_at')) {
                $columnsToDrop[] = 'validated_at';
            }
            if (Schema::hasColumn('stocks_regularisations', 'validated_by')) {
                $columnsToDrop[] = 'validated_by';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        // Recréation propre des colonnes et de la contrainte
        Schema::table('stocks_regularisations', function (Blueprint $table) {
            $table->timestamp('validated_at')->nullable()->after('status');
            $table->foreignId('validated_by')
                ->nullable()
                ->after('validated_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stocks_regularisations', function (Blueprint $table) {
            if (Schema::hasColumn('stocks_regularisations', 'validated_by')) {
                $table->dropForeign(['validated_by']);
            }

            $columnsToDrop = [];
            if (Schema::hasColumn('stocks_regularisations', 'validated_at')) {
                $columnsToDrop[] = 'validated_at';
            }
            if (Schema::hasColumn('stocks_regularisations', 'validated_by')) {
                $columnsToDrop[] = 'validated_by';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
