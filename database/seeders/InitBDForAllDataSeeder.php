<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class InitBDForAllDataSeeder extends Seeder
{
    public function run(): void
    {
        // CREATE USER SYSTEM
        $userSYSTEM = User::factory()->create([
            'nom_utilisateur' => 'SYSTEM',
            'login' => 'SYSTEM',
            'email' => 'system@system.sytem',
            'password' => \Hash::make('SYSTEM@2025'),
            'password_expiated_at' => now()->addDay(),
        ]);

        // Call Command for add all Default permission
        Artisan::call('permissions:extract');

        // Create Role Super Admin
        $role = Role::create([
            'name' => 'Super Admin',
            'description' => "Super utilisateur",
            'created_by' => $userSYSTEM->id,
            'updated_by' => $userSYSTEM->id
        ]);

        $role->permissions()->syncWithPivotValues(Permission::pluck('id')->toArray(), [
            'created_by' => $userSYSTEM->id,
            'updated_by' => $userSYSTEM->id
        ]);

        // Create SUPER ADMIN USER
        $superUser = User::factory()->create([
            'nom_utilisateur' => 'SUPER USER',
            'login' => 'admin',
            'email' => 'admin@admin.admin',
            'password' => \Hash::make('SUPERADMIN2145@2025'),
            'password_expiated_at' => now()->addDay(),
        ]);

        $role->users()->attach($superUser->id, [
            'created_by' => $userSYSTEM->id,
            'updated_by' => $userSYSTEM->id
        ]);
    }
}
