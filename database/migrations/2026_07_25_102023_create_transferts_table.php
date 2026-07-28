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
        Schema::create('transferts_stocks', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('emplacement_source_id')->constrained('emplacements_products');
            $table->foreignId('emplacement_destination_id')->constrained('emplacements_products');
            $table->string('staff_name');
            $table->text('description')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transferts_stocks');
    }
};
