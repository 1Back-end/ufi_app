<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('societes', function (Blueprint $table) {
            $table->id();
            $table->string('nom_soc_cli');
            $table->string('tel_soc_cli')->nullable();
            $table->string('adress_soc_cli')->nullable();
            $table->string('num_contrib_soc_cli')->nullable();
            $table->string('email_soc_cli')->nullable();
            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique('nom_soc_cli');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('societes');
    }
};
