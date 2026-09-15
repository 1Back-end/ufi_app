<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fournisseurs', function (Blueprint $table) {
            $table->string('address')->nullable()->change();
            $table->string('phone_number')->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->string('city')->nullable()->change();
            $table->string('country')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('fournisseurs', function (Blueprint $table) {
            $table->string('address')->nullable(false)->change();
            $table->string('phone_number')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
            $table->string('city')->nullable(false)->change();
            $table->string('country')->nullable(false)->change();
        });
    }
};
