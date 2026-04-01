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
        Schema::create('facture_assurances_by_centre', function (Blueprint $table) {
            $table->id();
            $table->string('object_of_facture_assurance');
            $table->string('mode_of_payment');
            $table->string('compte_or_payment');
            $table->string('number_for_compte')->nullable();
            $table->text('text_of_remerciement')->nullable();
            $table->foreignId('centre_id')->nullable()->constrained('centres')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facture_assurances_by_centre');
    }
};
