<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configtbl_categories_enquetes', function (Blueprint $table) {
            if (!Schema::hasColumn('configtbl_categories_enquetes', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('id');
            }
            if (!Schema::hasColumn('configtbl_categories_enquetes', 'order')) {
                $table->integer('order')->default(0)->after('is_active');
            }
            if (!Schema::hasColumn('configtbl_categories_enquetes', 'code')) {
                $table->string('code')->nullable();
            }
            if (!Schema::hasColumn('configtbl_categories_enquetes', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('configtbl_categories_enquetes', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('configtbl_categories_enquetes', 'is_active')) $columns[] = 'is_active';
            if (Schema::hasColumn('configtbl_categories_enquetes', 'order')) $columns[] = 'order';
            if (Schema::hasColumn('configtbl_categories_enquetes', 'code')) $columns[] = 'code';

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }

            if (Schema::hasColumn('configtbl_categories_enquetes', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
