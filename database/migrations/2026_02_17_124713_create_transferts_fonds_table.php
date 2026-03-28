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
        Schema::create('transferts_fonds', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('caisse_depart_id')->constrained('caisses')->onDelete('cascade');
            $table->foreignId('caisse_reception_id')->constrained('caisses')->onDelete('cascade');
            $table->enum('status', ['pending', 'validated', 'cancelled'])->default('pending');
            $table->decimal('montant_send', 15, 2);
            $table->foreignId('send_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('centre_id')->nullable()->constrained('centres')->nullOnDelete();
            $table->enum('type', ['debit'])->default('debit');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transferts_fonds');
    }
};
