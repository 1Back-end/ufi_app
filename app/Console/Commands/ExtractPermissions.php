<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use ReflectionClass;
use ReflectionMethod;

class ExtractPermissions extends Command
{
    protected $signature = 'permissions:extract';
    protected $description = 'Extract @permission and @permission_desc from all controllers';

    public function handle(): void
    {
        $controllersPath = app_path('Http/Controllers');
        $permissionsFromControllers = [];

        $userSYSTEM = User::whereLogin('SYSTEM')->first();

        // 1️⃣ Extraire les permissions des controllers
        foreach ($this->getControllers($controllersPath) as $controller) {
            $this->extractPermissionsFromController($controller, $permissionsFromControllers);
        }

        // 2️⃣ Traiter chaque permission extraite
        foreach ($permissionsFromControllers as $controller => $methods) {
            foreach ($methods as $method => $perm) {
                if (empty($perm['permission'])) continue;

                $permission = Permission::where('name', $perm['permission'])->first();

                if ($permission) {
                    // Mise à jour si nécessaire
                    if ($permission->description !== $perm['permission_desc']) {
                        $permission->update([
                            'description' => $perm['permission_desc'],
                            'updated_by' => $userSYSTEM->id
                        ]);
                        $this->line("✏️  Permission mise à jour: {$perm['permission']} ({$method})");
                    } else {
                        $this->line("✅  Permission inchangée: {$perm['permission']} ({$method})");
                    }
                } else {
                    // Création
                    $permission = Permission::create([
                        'name' => $perm['permission'],
                        'description' => $perm['permission_desc'],
                        'system' => true,
                        'created_by' => $userSYSTEM->id,
                        'updated_by' => $userSYSTEM->id
                    ]);
                    $this->line("🆕  Nouvelle permission créée: {$perm['permission']} ({$method})");

                    // Associer au rôle admin
                    $role = Role::find(1);
                    if ($role) {
                        $role->permissions()->syncWithPivotValues([$permission->id], [
                            'created_by' => $userSYSTEM->id,
                            'updated_by' => $userSYSTEM->id
                        ], false);
                        $this->line("🔗  Permission ajoutée au rôle : {$role->name}");
                    }
                }
            }
        }

        // 3️⃣ Supprimer les permissions obsolètes
        $allControllerPermissions = collect($permissionsFromControllers)
            ->flatMap(fn($methods) => collect($methods)->pluck('permission'))
            ->filter()
            ->toArray();

        Permission::where('system', true)
            ->whereNotIn('name', $allControllerPermissions)
            ->each(function ($permission) {
                $permission->delete();
                $this->line("❌  Permission supprimée: {$permission->name}");
            });

        $this->info('🎯 Extraction et synchronisation des permissions terminées.');
    }

    private function getControllers($directory, $namespace = 'App\\Http\\Controllers'): array
    {
        $controllers = [];
        $files = scandir($directory);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $fullPath = $directory . DIRECTORY_SEPARATOR . $file;

            if (is_dir($fullPath)) {
                $subNamespace = $namespace . '\\' . $file;
                $controllers = array_merge($controllers, $this->getControllers($fullPath, $subNamespace));
            } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $controllers[] = $namespace . '\\' . pathinfo($file, PATHINFO_FILENAME);
            }
        }

        return $controllers;
    }

    private function extractPermissionsFromController($controller, &$permissions): void
    {
        if (!class_exists($controller)) return;

        $reflection = new ReflectionClass($controller);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $docComment = $method->getDocComment();
            if ($docComment) {
                $permission = $this->extractTagValue($docComment, '@permission');
                $permissionDesc = $this->extractTagValue($docComment, '@permission_desc');

                if ($permission || $permissionDesc) {
                    $permissions[$controller][$method->getName()] = [
                        'permission' => $permission,
                        'permission_desc' => $permissionDesc,
                    ];
                }
            }
        }
    }

    private function extractTagValue($doc, $tag): ?string
    {
        if (preg_match('/' . preg_quote($tag) . '\s+(.+)/', $doc, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }
}
