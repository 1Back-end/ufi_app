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
        Schema::table('products', function (Blueprint $table) {
            $table->string('unite_par_emballage')->nullable()->change();
            $table->string('condition_par_unite_emballage')->nullable()->change();
            $table->string('Dosage_defaut')->nullable()->change();
            $table->string('schema_administration')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('unite_par_emballage')->nullable(false)->change();
            $table->string('condition_par_unite_emballage')->nullable(false)->change();
            $table->string('Dosage_defaut')->nullable(false)->change();
            $table->string('schema_administration')->nullable(false)->change();
        });
    }
};
