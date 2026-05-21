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
        DB::table('consultants')->where('TelWhatsApp', 'Oui')->update(['TelWhatsApp' => '1']);
        DB::table('consultants')->where('TelWhatsApp', 'Non')->update(['TelWhatsApp' => '0']);

        DB::table('consultants')->whereNotIn('TelWhatsApp', ['1', '0'])->update(['TelWhatsApp' => null]);

        Schema::table('consultants', function (Blueprint $table) {
            $table->boolean('TelWhatsApp')->nullable()->default(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultants', function (Blueprint $table) {
            $table->string('TelWhatsApp')->nullable()->change();
        });
        DB::table('consultants')->where('TelWhatsApp', '1')->update(['TelWhatsApp' => 'Oui']);
        DB::table('consultants')->where('TelWhatsApp', '0')->update(['TelWhatsApp' => 'Non']);
    }
};
