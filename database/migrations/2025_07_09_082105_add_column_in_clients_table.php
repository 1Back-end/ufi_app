<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('religion')->nullable();
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('confidential')->default(false)->comment('Permet à un rôle d\'afficher les données confidentielles');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('religion');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('confidential');
        });
    }
};
