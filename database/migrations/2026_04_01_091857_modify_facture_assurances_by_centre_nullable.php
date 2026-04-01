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
        Schema::table('facture_assurances_by_centre', function (Blueprint $table) {
            $table->string('object_of_facture_assurance')->nullable()->change();
            $table->string('mode_of_payment')->nullable()->change();
            $table->string('compte_or_payment')->nullable()->change();
            $table->string('number_for_compte')->nullable()->change();
            $table->text('text_of_remerciement')->nullable()->change();
            $table->foreignId('centre_id')->nullable()->change();
            $table->foreignId('created_by')->nullable()->change();
            $table->foreignId('updated_by')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facture_assurances_by_centre', function (Blueprint $table) {
            $table->string('object_of_facture_assurance')->nullable(false)->change();
            $table->string('mode_of_payment')->nullable(false)->change();
            $table->string('compte_or_payment')->nullable(false)->change();
            $table->string('number_for_compte')->nullable(false)->change();
            $table->text('text_of_remerciement')->nullable(false)->change();
            $table->foreignId('centre_id')->nullable(false)->change();
            $table->foreignId('created_by')->nullable(false)->change();
            $table->foreignId('updated_by')->nullable(false)->change();
        });
    }
};
