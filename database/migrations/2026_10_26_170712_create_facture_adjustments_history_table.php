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
        Schema::create('facture_adjustments_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facture_id')->constrained('factures')->cascadeOnDelete();
            $table->foreignId('prestation_id')->nullable()->constrained('prestations')->nullOnDelete();

            $table->decimal('amount_insurance', 12, 2)->default(0);
            $table->decimal('amount_insured', 12, 2)->default(0);

            $table->string('payable_by_file_url')->nullable();
            $table->text('description');

            $table->foreignId('adjusted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facture_adjustments_history');
    }
};
