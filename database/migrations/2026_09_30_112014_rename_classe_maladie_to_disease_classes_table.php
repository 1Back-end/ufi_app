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
        Schema::rename('classe_maladie', 'disease_classes');

        Schema::table('disease_classes', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disease_classes', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::rename('disease_classes', 'classe_maladie');
    }
};
