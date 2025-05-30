<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('facture_associates', function (Blueprint $table) {
            $table->id();
            $table->morphs('facturable');
            $table->string('code');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('amount');
            $table->dateTime('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facture_associates');
    }
};
