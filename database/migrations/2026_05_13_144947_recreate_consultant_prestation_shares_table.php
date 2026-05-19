<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('consultant_prestation_shares');

        Schema::create('consultant_prestation_shares', function (Blueprint $table) {

            $table->id();

            $table->foreignId('consultant_id')
                ->constrained('consultants')
                ->cascadeOnDelete();

            $table->foreignId('prestation_type_id')
                ->constrained('type_prestations')
                ->cascadeOnDelete();

            $table->decimal('share_rate', 5, 2)->default(0);

            $table->enum('calculation_type', ['percentage', 'fixed'])
                ->default('percentage');

            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();

            // ✅ FIX ICI (nom court obligatoire)
            $table->unique(
                ['consultant_id', 'prestation_type_id'],
                'consultant_prestation_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultant_prestation_shares');
    }
};
