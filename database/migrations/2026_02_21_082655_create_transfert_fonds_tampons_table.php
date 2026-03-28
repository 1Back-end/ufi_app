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
        Schema::create('transfert_fonds_tampons', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();

            $table->foreignId('caisse_depart_id')
                ->constrained('caisses')
                ->cascadeOnDelete();

            $table->foreignId('caisse_reception_id')
                ->constrained('caisses')
                ->cascadeOnDelete();

            $table->decimal('montant_send', 15, 2);

            $table->enum('type', ['debit'])->default('debit');
            $table->enum('status', ['pending', 'validated', 'cancelled'])
                ->default('pending');

            $table->foreignId('send_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
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

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfert_fonds_tampons');
    }
};
