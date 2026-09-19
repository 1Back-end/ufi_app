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
            $table->boolean('has_familial')->default(false)->after('pas_d_antecedent');
            $table->text('familial_description')->nullable()->after('has_familial');
            $table->boolean('has_personnel')->default(false)->after('familial_description');
            $table->text('personnel_description')->nullable()->after('has_personnel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ops_tbl_antecedents', function (Blueprint $table) {
            $table->dropColumn([
                'has_familial',
                'familial_description',
                'has_personnel',
                'personnel_description'
            ]);
        });
    }
};
