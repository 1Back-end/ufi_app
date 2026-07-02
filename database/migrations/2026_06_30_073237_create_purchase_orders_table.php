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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('purchase_order_number')->unique();
            $table->string('purchase_order_type')->default('external');
            $table->date('order_date')->nullable();
            $table->date('expected_delivery_date')->nullable();

            $table->foreignId('fournisseur_id')
                ->nullable()
                ->constrained('fournisseurs')
                ->nullOnDelete();

            $table->foreignId('destination_location_id')
                ->nullable()
                ->constrained('emplacements_products')
                ->restrictOnDelete();

            $table->string('status')->default('pending');

            $table->text('description')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
