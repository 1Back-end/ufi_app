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
        Schema::table('type_prestations', function (Blueprint $table) {
            $table->unsignedBigInteger('centre_id')->nullable()->after('id');
            $table->foreign('centre_id')->references('id')->on('centres')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('type_prestations', function (Blueprint $table) {
            $table->dropForeign(['centre_id']);
            $table->dropColumn('centre_id');
        });
    }
};
