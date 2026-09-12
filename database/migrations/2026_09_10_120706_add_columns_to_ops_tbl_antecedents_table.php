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
        Schema::table('ops_tbl_antecedents', function (Blueprint $table) {
            if (!Schema::hasColumn('ops_tbl_antecedents', 'dossier_consultation_id')) {
                $table->unsignedBigInteger('dossier_consultation_id')->nullable()->after('id');
                $table->foreign('dossier_consultation_id')->references('id')->on('dossiers_consultations')->onDelete('cascade');
            }
            if (!Schema::hasColumn('ops_tbl_antecedents', 'categorie_antecedent_id')) {
                $table->unsignedBigInteger('categorie_antecedent_id')->nullable();
            }
            if (!Schema::hasColumn('ops_tbl_antecedents', 'souscategorie_antecedent_id')) {
                $table->unsignedBigInteger('souscategorie_antecedent_id')->nullable();
            }
            if (!Schema::hasColumn('ops_tbl_antecedents', 'category_label')) {
                $table->string('category_label')->nullable();
            }
            if (!Schema::hasColumn('ops_tbl_antecedents', 'sous_categorie_label')) {
                $table->string('sous_categorie_label')->nullable();
            }
            if (!Schema::hasColumn('ops_tbl_antecedents', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('ops_tbl_antecedents', 'pas_d_antecedent')) {
                $table->boolean('pas_d_antecedent')->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ops_tbl_antecedents', function (Blueprint $table) {
            try {
                $table->dropForeign(['dossier_consultation_id']);
            } catch (\Exception $e) {}

            $columns = [
                'dossier_consultation_id',
                'categorie_antecedent_id',
                'souscategorie_antecedent_id',
                'category_label',
                'sous_categorie_label',
                'description',
                'pas_d_antecedent',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('ops_tbl_antecedents', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
