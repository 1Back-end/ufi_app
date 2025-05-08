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
        Schema::create('prise_en_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assureur_id')->references('id')->on('assureurs')->Ondelete('cascade');
            $table->foreignId('quotation_id')->constrained('quotations')->onDelete('cascade');
            $table->string('code');
            $table->date('date');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->foreignId('client_id')->references('id')->on('clients')->Ondelete('cascade');
            $table->string('taux_pc');
            $table->string('usage_unique')->default('Non');
            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prise_en_charges');
    }
};
