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
        Schema::create('config_tbl_categorie_antecedents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('souscategorie_antecedent_id');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
            $table->foreign('souscategorie_antecedent_id', 'fk_souscat')
                ->references('id')
                ->on('configtbl_souscategorie_antecedent')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_tbl_categorie_antecedents');
    }
};
