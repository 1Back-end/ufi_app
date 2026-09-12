<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ops_tbl_antecedents', function (Blueprint $table) {
            $table->unsignedBigInteger('categorie_antecedent_id')->nullable()->change();
            $table->unsignedBigInteger('souscategorie_antecedent_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ops_tbl_antecedents', function (Blueprint $table) {
            $table->unsignedBigInteger('categorie_antecedent_id')->nullable(false)->change();
            $table->unsignedBigInteger('souscategorie_antecedent_id')->nullable(false)->change();
        });
    }
};
