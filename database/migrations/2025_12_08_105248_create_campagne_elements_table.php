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
        Schema::create('campagne_elements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('campagne_id')->constrained('campagnes')->cascadeOnDelete();
            $table->enum('type', ['examens','consultations','actes','soins']);
            $table->unsignedBigInteger('element_id');
            $table->decimal('price', 10, 2)->default(0);
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
        Schema::dropIfExists('campagne_elements');
    }
};
