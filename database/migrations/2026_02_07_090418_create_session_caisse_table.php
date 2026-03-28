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
        Schema::create('session_caisse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('caisse_id')->nullable()->constrained('caisses')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('centre_id')->nullable()->constrained('centres')->nullOnDelete();
            $table->timestamp('ouverture_ts')->nullable();
            $table->timestamp('fermeture_ts')->nullable();

            $table->decimal('fonds_ouverture', 15, 2)->default(0);
            $table->decimal('fonds_fermeture', 15, 2)->nullable();

            $table->decimal('solde', 15, 2)->default(0);

            $table->string('etat')->nullable();
            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_caisse');
    }
};
