<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prestations', function (Blueprint $table) {
            if (Schema::hasColumn('prestations', 'type_visite')) {
                $table->dropColumn('type_visite');
            }
        });

        Schema::table('prestations', function (Blueprint $table) {
            $table->string('type_visite')->default('others');
        });
    }

    public function down(): void
    {
        Schema::table('prestations', function (Blueprint $table) {
            if (Schema::hasColumn('prestations', 'type_visite')) {
                $table->dropColumn('type_visite');
            }
        });
    }
};
