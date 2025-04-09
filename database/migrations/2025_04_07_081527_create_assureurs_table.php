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
        Schema::create('assureurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('nom_abrege')->nullable()->comment('Calculé automatiquement ou renseigné');
            $table->string('adresse');
            $table->string('tel')->unique()->nullable();
            $table->string('tel1')->unique()->nullable();
            $table->string('ref')->unique()->nullable();
            $table->foreignId('code_quotation')->constrained('quotations')->onDelete('cascade')->comment('Table ConfigTbl_Quotation');
            $table->foreignId('code_centre')->constrained('centres')->onDelete('cascade');
            $table->string('Reg_com',20)->unique(); // Registre de commerce unique
            $table->string('num_com',20)->unique(); // Numéro contribuable unique
            $table->integer('bp');
            $table->string('fax');
            $table->string('code_type')->default('Principale')->comment('Valeurs: "Principale" ou "Auxiliaire"');
            $table->string('code_main')->nullable()->comment('0 si code_type_assur="Auxilliaire" alors une assurance existante dans la liste')->nullable();
            $table->integer('ref_assur_principal')->comment('Réference assureur principal à selectionner dans la liste des assurances existantes')->nullable();
            $table->string('email')->unique();
            $table->boolean('BM')->default(false)->comment('quand BM=1, le second prix des prestations est appliqué pour cet assurance');
            $table->string('status')->default('Actif')->comment('"Actif" ou "Inactif"');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_deleted')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assureurs');
    }
};
