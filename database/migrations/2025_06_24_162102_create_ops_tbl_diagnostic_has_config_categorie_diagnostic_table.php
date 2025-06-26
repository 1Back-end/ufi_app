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
        Schema::create('ops_tbl_diagnostic_has_config_categorie_diagnostic', function (Blueprint $table) {
            $table->id();

            $table->foreignId('code_diagnostic_id');
            $table->foreignId('categorie_diagnostic_id');

            // Ajout des clés étrangères avec noms courts
            $table->foreign('code_diagnostic_id', 'fk_diag_codediag')
                ->references('id')->on('ops_tbl_diagnostic')
                ->onDelete('cascade');

            $table->foreign('categorie_diagnostic_id', 'fk_diag_categorie')
                ->references('id')->on('categorie_diagnostic')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ops_tbl_diagnostic_has_config_categorie_diagnostic');
    }
};
