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
        Schema::create('reglements_assurances', function (Blueprint $table) {
            $table->id();

            $table->decimal('amount_total', 15, 2); // montant total saisi
            $table->decimal('ir_total', 15, 2)->default(0); // IR global
            $table->decimal('net_amount', 15, 2); // montant net

            $table->boolean('apply_ir_global')->default(false);
            $table->decimal('ir_rate_global', 5, 2)->nullable();

            $table->unsignedBigInteger('assurance_id')->nullable();

            $table->string('type')->default('assurance');

            $table->timestamp('reglement_date_sart')->nullable();
            $table->timestamp('reglement_date_end')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('assurance_id')->references('id')->on('assureurs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reglements_assurances');
    }
};
