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
            $table->foreignId('assureurs_id')->references('id')->on('assureurs')->Ondelete('cascade');
            $table->foreignId('quotations_id')->constrained('quotations')->onDelete('cascade')->comment('Table ConfigTbl_Quotation');
            $table->date('date');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->foreignId('clients_id')->references('id')->on('clients')->Ondelete('cascade');
            $table->float('taux_pc');
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
