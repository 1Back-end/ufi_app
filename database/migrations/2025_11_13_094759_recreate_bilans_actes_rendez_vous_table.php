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
        Schema::dropIfExists('bilans_actes_rendez_vous');
        Schema::create('bilans_actes_rendez_vous', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rendez_vous_id')->constrained('rendez_vouses')->onDelete('cascade');
            $table->foreignId('prestation_id')->constrained('prestations')->onDelete('restrict');

            $table->string('medecin_signataire')->nullable();
            $table->string('technique_analyse')->nullable();
            $table->text('resume')->nullable();
            $table->text('conclusion')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
        });
        //
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bilans_actes_rendez_vous');
    }
};
