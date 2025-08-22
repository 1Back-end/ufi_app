<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('examens', function (Blueprint $table) {
            $table->unsignedBigInteger('type_prelevement_id')->nullable()->change();
            $table->unsignedBigInteger('paillasse_id')->nullable()->change();
            $table->unsignedBigInteger('tube_prelevement_id')->nullable()->change();
            $table->unsignedBigInteger('sub_family_exam_id')->nullable()->change();
            $table->unsignedBigInteger('kb_prelevement_id')->nullable()->change();
            $table->string('b')->nullable()->change();
            $table->string('b1')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('examens', function (Blueprint $table) {
            $table->unsignedBigInteger('type_prelevement_id')->change();
            $table->unsignedBigInteger('paillasse_id')->change();
            $table->unsignedBigInteger('tube_prelevement_id')->change();
            $table->unsignedBigInteger('sub_family_exam_id')->change();
            $table->unsignedBigInteger('kb_prelevement_id')->change();
            $table->string('b')->change();
            $table->string('b1')->change();
        });
    }
};
