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
        Schema::table('special_regulations', function (Blueprint $table) {
            $table->unsignedBigInteger('assureur_id')->nullable()->after('id');

            // 🔗 clé étrangère (optionnel mais recommandé)
            $table->foreign('assureur_id')
                ->references('id')
                ->on('assureurs')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('special_regulations', function (Blueprint $table) {
            $table->dropForeign(['assureur_id']);
            $table->dropColumn('assureur_id');
        });
    }
};
