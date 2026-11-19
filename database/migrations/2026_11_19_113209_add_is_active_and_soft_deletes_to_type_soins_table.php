<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('type_soins', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('type_soins', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('is_active');
        });
    }
};
