<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('prestations', function (Blueprint $table) {
            $table->unsignedBigInteger('convention_id')->nullable()->after('payable_by');
            $table->foreign('convention_id')->references('id')->on('convention_associes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('prestations', function (Blueprint $table) {
            $table->dropForeign('convention_id');
        });
    }
};
