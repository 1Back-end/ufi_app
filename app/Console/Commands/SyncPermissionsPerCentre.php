<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Centre;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

class SyncPermissionsPerCentre extends Command
{
    protected $signature = 'permissions:sync-centres';
    protected $description = 'Synchronise toutes les permissions pour chaque centre (users + roles)';

    public function handle(): void
    {
        $centres = Centre::all();
        $permissions = Permission::where('active', true)->get();

        $this->info("Centres: " . $centres->count());
        $this->info("Permissions: " . $permissions->count());

        foreach ($centres as $centre) {

            $this->info("➡ Centre: {$centre->name}");

            foreach ($permissions as $permission) {

                // 🔹 USERS direct permissions
                $users = User::whereHas('permissions', function ($q) use ($permission) {
                    $q->where('permissions.id', $permission->id);
                })->get();

                foreach ($users as $user) {

                    $exists = $user->permissions()
                        ->where('permission_id', $permission->id)
                        ->wherePivot('centre_id', $centre->id)
                        ->exists();

                    if (!$exists) {
                        $user->permissions()->attach($permission->id, [
                            'centre_id' => $centre->id,
                            'active' => true,
                            'created_by' => 1,
                            'updated_by' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                // 🔹 ROLES permissions
                $roles = Role::whereHas('permissions', function ($q) use ($permission) {
                    $q->where('permissions.id', $permission->id);
                })->get();

                foreach ($roles as $role) {

                    $exists = $role->permissions()
                        ->where('permission_id', $permission->id)
                        ->wherePivot('centre_id', $centre->id)
                        ->exists();

                    if (!$exists) {
                        $role->permissions()->attach($permission->id, [
                            'centre_id' => $centre->id,
                            'active' => true,
                            'created_by' => 1,
                            'updated_by' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        $this->info("✅ Synchronisation complète terminée !");
    }
}
