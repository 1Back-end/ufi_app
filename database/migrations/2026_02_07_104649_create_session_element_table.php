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
        Schema::create('session_element', function (Blueprint $table) {
            $table->id();

            $table->foreignId('session_id')->constrained('session_caisse')->onDelete('cascade');
            $table->foreignId('facture_id')->constrained('factures')->onDelete('cascade');

            $table->decimal('montant', 15, 2)->default(0);

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
        Schema::dropIfExists('session_element');
    }
};
