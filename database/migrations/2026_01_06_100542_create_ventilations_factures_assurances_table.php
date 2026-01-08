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
        Schema::create('ventilations_assurances_factures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('ventilation_date');
            $table->string('piece_number');
            $table->date('piece_date');
            $table->decimal('total_amount', 15, 2);
            $table->text('comment')->nullable();
            $table->foreignId('regulation_method_id')->constrained('regulation_methods')->cascadeOnDelete();
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
        Schema::dropIfExists('ventilations_assurances_factures');
    }
};
