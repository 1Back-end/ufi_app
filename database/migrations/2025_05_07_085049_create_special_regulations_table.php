<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('special_regulations', function (Blueprint $table) {
            $table->id();
            $table->morphs('regulation');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('amount');
            $table->unsignedBigInteger('regulation_method_id');
            $table->string('number_piece');
            $table->date('date_piece');
            $table->timestamps();
            $table->foreign('regulation_method_id')->references('id')->on('regulation_methods');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_regulations');
    }
};
