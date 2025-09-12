<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedBigInteger('status_familiale_id')->nullable()->change();
            $table->unsignedBigInteger('type_document_id')->nullable()->change();
            $table->unsignedBigInteger('sexe_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedBigInteger('status_familiale_id')->change();
            $table->unsignedBigInteger('type_document_id')->change();
            $table->unsignedBigInteger('sexe_id')->change();
        });
    }
};
