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
        // Table principale : proformas
        Schema::create('proformas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
            $table->enum('b_global', ['b', 'b1'])->nullable();
            $table->boolean('proforma')->default(true); // appliquer prélèvement
            $table->decimal('total', 15, 2)->default(0);
            $table->boolean('is_deleted')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Table des prestations liées : proforma_items
        Schema::create('proforma_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proforma_id')->constrained('proformas')->onDelete('cascade');
            $table->string('name'); // nom de l'examen, acte ou consultation
            $table->decimal('unit_price', 15, 2); // prix unitaire calculé
            $table->decimal('kb_prelevement', 15, 2)->default(0); // prélèvement si appliqué
            $table->decimal('total', 15, 2); // total par prestation
            $table->integer('type')->nullable()->default(1); // type de prestation (examen, consultation, acte)
            $table->boolean('is_deleted')->default(false);
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
        Schema::dropIfExists('proforma_items'); // supprimer d'abord les items
        Schema::dropIfExists('proformas');
    }
};
