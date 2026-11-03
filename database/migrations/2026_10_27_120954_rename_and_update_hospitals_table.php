<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $currentName = null;
        if (Schema::hasTable('hopitals')) {
            $currentName = 'hopitals';
        } elseif (Schema::hasTable('hospitals')) {
            $currentName = 'hospitals';
        }

        if ($currentName && $currentName !== 'consultants_hospitals') {
            Schema::rename($currentName, 'consultants_hospitals');
        }
        if (Schema::hasTable('consultants_hospitals')) {
            Schema::table('consultants_hospitals', function (Blueprint $table) {
                if (Schema::hasColumn('consultants_hospitals', 'is_deleted')) {
                    $table->dropColumn('is_deleted');
                }

                if (!Schema::hasColumn('consultants_hospitals', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }

                if (!Schema::hasColumn('consultants_hospitals', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable();
                    $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                }

                if (!Schema::hasColumn('consultants_hospitals', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable();
                    $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
                }
                if (!Schema::hasColumn('consultants_hospitals', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        if (Schema::hasTable('consultants_hospitals')) {
            Schema::rename('consultants_hospitals', 'hopitals');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};
