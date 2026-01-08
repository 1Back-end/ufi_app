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
        Schema::table('factures', function (Blueprint $table) {
            $table->integer('price_bay_by_assurance')
                ->default(0)
                ->after('amount_pc'); // adapte si besoin
            $table->string('description_bay_by_assurance')->nullable()->after('price_bay_by_assurance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            $table->dropColumn('description_bay_by_assurance');
            $table->dropColumn('price_bay_by_assurance');
        });
    }
};
