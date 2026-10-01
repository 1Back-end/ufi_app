<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('maladie_type_diagnostic', 'disease_diagnostics');
        Schema::table('disease_diagnostics', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('disease_diagnostics', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::rename('disease_diagnostics', 'maladie_type_diagnostic');
    }
};
