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
        Schema::create('ops_tbl_mise_en_observation_hospitalisation', function (Blueprint $table) {
            $table->id();
            $table->text('observation')->nullable();
            $table->text('resume')->nullable();
            $table->integer('nbre_jours')->nullable();

            $table->unsignedBigInteger('rapport_consultation_id')->nullable();
            $table->unsignedBigInteger('infirmiere_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->boolean('is_deleted')->default(false);
            $table->timestamps();

            // Clés étrangères avec noms courts
            $table->foreign('rapport_consultation_id', 'fk_obs_rapport')
                ->references('id')->on('ops_tbl_rapport_consultations')->nullOnDelete();

            $table->foreign('infirmiere_id', 'fk_obs_infirmiere')
                ->references('id')->on('nurses')->nullOnDelete();

            $table->foreign('created_by', 'fk_obs_created_by')
                ->references('id')->on('users')->nullOnDelete();

            $table->foreign('updated_by', 'fk_obs_updated_by')
                ->references('id')->on('users')->nullOnDelete();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ops_tbl_mise_en_observation_hospitalisation');
    }
};
