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
        Schema::table('ops_tbl_referre_medical', function (Blueprint $table) {
            $table->string('code_prescripteur')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ops_tbl_referre_medical', function (Blueprint $table) {
            $table->dropColumn('code_prescripteur');
        });
    }
};
