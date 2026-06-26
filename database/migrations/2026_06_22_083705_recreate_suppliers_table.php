<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('fournisseurs');

        Schema::enableForeignKeyConstraints();

        Schema::create('fournisseurs', function (Blueprint $table) {

            $table->id();

            $table->string('full_name');
            $table->string('company_name')->nullable()->unique();

            $table->string('address');
            $table->string('phone_number');
            $table->string('second_phone_number')->nullable();

            $table->string('email');

            $table->string('business_registration_number')->nullable();
            $table->string('website')->nullable();

            $table->string('city');
            $table->string('country');

            $table->string('tax_number')->nullable();

            $table->string('contact_person')->nullable();
            $table->string('contact_person_phone')->nullable();

            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fournisseurs');
    }
};
