<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $firstUser = DB::table('users')->first();
        $userId = $firstUser ? $firstUser->id : 1;

        DB::table('settings')->updateOrInsert(
            ['key' => 'inactivity_timeout_minutes'],
            [
                'value'       => '30',
                'description' => 'Délai d\'inactivité en minutes avant déconnexion automatique',
                'created_by'  => $userId,
                'updated_by'  => $userId,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]
        );
    }
}
