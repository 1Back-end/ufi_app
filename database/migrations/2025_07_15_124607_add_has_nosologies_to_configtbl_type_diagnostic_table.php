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
        Schema::table('configtbl_type_diagnostic', function (Blueprint $table) {
            $table->boolean('has_nosologies')
                ->default(false)
                ->after('description');   // place le champ juste après 'description'
        });
    }

    public function down(): void
    {
        Schema::table('configtbl_type_diagnostic', function (Blueprint $table) {
            $table->dropColumn('has_nosologies');
        });
    }
};
