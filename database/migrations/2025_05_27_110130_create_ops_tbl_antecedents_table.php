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
        Schema::create('ops_tbl_antecedents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('categorie_antecedent_id');
            $table->unsignedBigInteger('souscategorie_antecedent_id');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();
            $table->foreign('categorie_antecedent_id')->references('id')->on('config_tbl_categorie_antecedents')->restrictOnDelete();
            $table->foreign('souscategorie_antecedent_id')->references('id')->on('configtbl_souscategorie_antecedent')->restrictOnDelete();
            $table->boolean('is_deleted')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ops_tbl_antecedents');
    }
};
