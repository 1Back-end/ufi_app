<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            DB::statement('ALTER TABLE consultant_specialties DROP INDEX consultant_specialties_nom_specialite_unique');
        } catch (\Exception $e) {
            try {
                DB::statement('ALTER TABLE consultant_specialties DROP INDEX nom_specialite');
            } catch (\Exception $ex) {
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultant_specialties', function (Blueprint $table) {
            $table->unique('nom_specialite');
        });
    }
};
