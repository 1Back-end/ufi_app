<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 🔥 PRESTATIONS
        if (!$this->indexExists('prestations', 'idx_prestations_centre')) {
            Schema::table('prestations', function (Blueprint $table) {
                $table->index('centre_id', 'idx_prestations_centre');
            });
        }

        if (!$this->indexExists('prestations', 'idx_prestations_client')) {
            Schema::table('prestations', function (Blueprint $table) {
                $table->index('client_id', 'idx_prestations_client');
            });
        }

        if (!$this->indexExists('prestations', 'idx_prestations_regulated')) {
            Schema::table('prestations', function (Blueprint $table) {
                $table->index('regulated', 'idx_prestations_regulated');
            });
        }

        // 🔥 FACTURES
        if (!$this->indexExists('factures', 'idx_factures_prestation')) {
            Schema::table('factures', function (Blueprint $table) {
                $table->index('prestation_id', 'idx_factures_prestation');
            });
        }
    }

    public function down(): void
    {
        // 🔥 PRESTATIONS
        if ($this->indexExists('prestations', 'idx_prestations_centre')) {
            Schema::table('prestations', function (Blueprint $table) {
                $table->dropIndex('idx_prestations_centre');
            });
        }

        if ($this->indexExists('prestations', 'idx_prestations_client')) {
            Schema::table('prestations', function (Blueprint $table) {
                $table->dropIndex('idx_prestations_client');
            });
        }

        if ($this->indexExists('prestations', 'idx_prestations_regulated')) {
            Schema::table('prestations', function (Blueprint $table) {
                $table->dropIndex('idx_prestations_regulated');
            });
        }

        // 🔥 FACTURES
        if ($this->indexExists('factures', 'idx_factures_prestation')) {
            Schema::table('factures', function (Blueprint $table) {
                $table->dropIndex('idx_factures_prestation');
            });
        }
    }

    // 🔥 Fonction utilitaire
    private function indexExists($table, $indexName)
    {
        $result = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($result) > 0;
    }
};
