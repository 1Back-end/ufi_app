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
        Schema::create('maladie_type_diagnostic', function (Blueprint $table) {
            $table->id();

            $table->foreignId('maladie_id')->constrained('maladies')->onDelete('cascade');

            $table->foreignId('type_diagnostic_id')->constrained('configtbl_type_diagnostic')->onDelete('cascade');

            $table->foreignId('rapport_consultations_id')->nullable()
                ->constrained('ops_tbl_rapport_consultations')
                ->nullOnDelete()
                ->after('type_diagnostic_id');

            $table->text('description')->nullable();

            $table->boolean('is_deleted')->default(false);

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
        Schema::dropIfExists('maladie_type_diagnostic');
    }
};
