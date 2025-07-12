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
        Schema::table('ops_tbl_referre_medical', function (Blueprint $table) {
            $table->enum('type_prescripteur', ['Prescripteur Interne', 'Prescripteur Externe'])->nullable()->after('description');

            $table->foreignId('consultant_id')
                ->nullable()
                ->constrained('consultants') // ou 'users' selon ta logique
                ->nullOnDelete()
                ->after('type_prescripteur');

            $table->foreignId('prescripteur_id')
                ->nullable()
                ->constrained('prescripteurs')
                ->nullOnDelete()
                ->after('consultant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ops_tbl_referre_medical', function (Blueprint $table) {
            $table->dropForeign(['consultant_id']);
            $table->dropColumn('consultant_id');

            $table->dropForeign(['prescripteur_id']);
            $table->dropColumn('prescripteur_id');

            $table->dropColumn('type_prescripteur');
        });

    }
};
