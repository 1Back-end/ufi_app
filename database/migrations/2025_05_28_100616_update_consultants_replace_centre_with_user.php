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
        Schema::table('consultants', function (Blueprint $table) {
            // Supprimer la clé étrangère et la colonne centre_id
            $table->dropForeign(['centre_id']);
            $table->dropColumn('centre_id');

            // Ajouter user_id avec clé étrangère
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
        });
        //
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultants', function (Blueprint $table) {
            // Supprimer user_id
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');

            // Restaurer centre_id
            $table->foreignId('centre_id')
                ->nullable()
                ->constrained('centres')
                ->onDelete('set null');
        });
        //
    }
};
