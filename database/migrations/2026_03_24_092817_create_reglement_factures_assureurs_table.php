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
        Schema::create('reglement_factures_assureurs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('reglement_assurance_id');
            $table->unsignedBigInteger('facture_id');

            $table->decimal('montant_initial', 15, 2);
            $table->decimal('montant_assure', 15, 2);
            $table->decimal('montant_ir', 15, 2)->default(0);
            $table->decimal('montant_exclu', 15, 2)->default(0);

            $table->string('type_label')->nullable();

            $table->foreign('reglement_assurance_id')->references('id')->on('reglements_assurances')->onDelete('cascade');

            $table->foreign('facture_id')->references('id')->on('factures')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reglement_factures_assureurs');
    }
};
