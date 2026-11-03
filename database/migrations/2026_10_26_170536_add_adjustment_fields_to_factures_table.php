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
            $table->string('payable_by_file_url')->nullable();
            $table->decimal('amount_insurance', 12, 2)->default(0);
            $table->decimal('amount_insured', 12, 2)->default(0);
            $table->text('description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            $table->dropColumn([
                'payable_by_file_url',
                'amount_insurance',
                'amount_insured',
                'description'
            ]);
        });
    }
};
