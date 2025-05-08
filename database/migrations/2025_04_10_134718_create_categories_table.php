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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();  // Le nom de la catégorie
            $table->foreignId('group_product_id')->constrained('group_products')->onDelete('cascade')->comment('Creer Table OpsTbl_GroupeProduit: Anesthesiques, Antifongiques, Anti-infectieux, Anti-biotiques, etc.');
            $table->string('description')->nullable();  // Description optionnelle de la catégorie
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
