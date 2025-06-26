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
        Schema::create('config_tbl_maladie_diagnostic', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sous_categorie_id')->constrained('config_sous_categorie_diagnostic')->onDelete('cascade');
            $table->string('name');
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
        Schema::dropIfExists('config_tbl_maladie_diagnostic');
    }
};
