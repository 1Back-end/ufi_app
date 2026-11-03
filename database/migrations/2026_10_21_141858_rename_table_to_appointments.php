<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rendez_vouses') && !Schema::hasTable('appointments')) {
            Schema::rename('rendez_vouses', 'appointments');
        } elseif (Schema::hasTable('rendez_vous') && !Schema::hasTable('appointments')) {
            Schema::rename('rendez_vous', 'appointments');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('appointments') && !Schema::hasTable('rendez_vouses')) {
            Schema::rename('appointments', 'rendez_vouses');
        }
    }
};
