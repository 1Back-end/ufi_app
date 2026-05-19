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
        Schema::create('consultant_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultant_id')->nullable()->constrained('consultants')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('payment_accounts')->nullOnDelete();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->foreignId('caisse_id')->nullable()->constrained('caisses')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultant_payments');
    }
};
