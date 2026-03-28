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
        Schema::create('transfert_grandes_caisses', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();

            // 🔹 Caisse départ (nullable)
            $table->foreignId('caisse_depart_id')
                ->nullable()
                ->constrained('caisses')
                ->nullOnDelete();

            // 🔹 Caisse réception (nullable)
            $table->foreignId('caisse_reception_id')
                ->nullable()
                ->constrained('caisses')
                ->nullOnDelete();

            $table->decimal('montant', 15, 2);

            $table->enum('status', ['pending', 'validated', 'cancelled'])
                ->default('pending');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('send_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('centre_id')
                ->nullable()
                ->constrained('centres')
                ->nullOnDelete();

            $table->foreignId('validated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('validated_at')->nullable();

            $table->foreignId('rejected_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('rejected_at')->nullable();
            $table->text('reason_for_rejection')->nullable();

            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfert_grandes_caisses');
    }
};
