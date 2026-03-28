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
        Schema::create('mouvements_caisses', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();

            $table->string('type')->nullable()->default('add');

            $table->foreignId('caisse_depart_id')->nullable()->constrained('caisses')->nullOnDelete();
            $table->foreignId('caisse_arrivee_id')->nullable()->constrained('caisses')->nullOnDelete();

            $table->integer('montant')->nullable()->default(0);

            $table->text('description')->nullable();

            $table->enum('status', ['pending', 'validated', 'cancelled'])->default('pending');
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
        Schema::dropIfExists('mouvements_caisses');
    }
};
