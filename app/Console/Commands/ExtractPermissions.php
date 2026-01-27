<?php

namespace App\Console\Commands;

use App\Models\CategoryPermission;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use ReflectionClass;
use ReflectionMethod;

class ExtractPermissions extends Command
{
    protected $signature = 'permissions:extract';
    protected $description = 'Synchronise les permissions avec les annotations des contrôleurs et les catégorise par menu.';

    public function handle(): void
    {
        $controllersPath = app_path('Http/Controllers');
        $permissions = [];

        $systemUser = User::where('login', 'SYSTEM')->first();
        $systemId = $systemUser?->id ?? 1;

        $superAdminRole = Role::find(1);

        // 1️⃣ Extraction des permissions depuis les contrôleurs
        foreach ($this->getControllers($controllersPath) as $controller) {
            $this->extractPermissionsFromController($controller, $permissions);
        }

        $this->info("\n--- Synchronisation des permissions ---");

        // 2️⃣ Création / mise à jour des permissions et catégories
        foreach ($permissions as $controller => $methods) {

            $categoryName = $this->extractControllerCategory($controller) ?? 'Autres';

            $category = CategoryPermission::firstOrCreate(
                ['name' => $categoryName],
                [
                    'description' => $categoryName,
                    'created_by' => $systemId,
                    'updated_by' => $systemId,
                ]
            );

            foreach ($methods as $perm) {
                if (empty($perm['permission'])) continue;

                $permission = Permission::updateOrCreate(
                    ['name' => $perm['permission']],
                    [
                        'description' => $perm['permission_desc'] ?? '',
                        'category_id' => $category->id,
                        'system' => true,
                        'active' => true,
                        'created_by' => $systemId,
                        'updated_by' => $systemId,
                    ]
                );

                $this->info(
                    $permission->wasRecentlyCreated
                        ? "✅ Créée : {$permission->name}"
                        : "🔁 Mise à jour : {$permission->name}"
                );

                // Assigner la permission au Super Admin si pas déjà assignée
                if ($superAdminRole && !$superAdminRole->permissions->contains($permission->id)) {
                    $superAdminRole->permissions()->attach($permission->id, [
                        'created_by' => $systemId,
                        'updated_by' => $systemId,
                    ]);
                }
            }
        }

        // 3️⃣ Suppression des catégories vides
        $usedCategoryIds = Permission::pluck('category_id')->unique()->filter();

        CategoryPermission::whereNotIn('id', $usedCategoryIds)
            ->each(function ($category) {
                $category->delete();
                $this->warn("🗑️ Catégorie supprimée : {$category->name}");
            });

        $this->info("\n✅ Synchronisation terminée avec succès !");
    }

    /**
     * Récupère tous les contrôleurs du dossier donné
     */
    private function getControllers(string $directory, string $namespace = 'App\\Http\\Controllers'): array
    {
        $controllers = [];
        foreach (scandir($directory) as $file) {
            if (in_array($file, ['.', '..'])) continue;

            $fullPath = $directory . DIRECTORY_SEPARATOR . $file;

            if (is_dir($fullPath)) {
                $controllers = array_merge(
                    $controllers,
                    $this->getControllers($fullPath, $namespace . '\\' . $file)
                );
            } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $controllers[] = $namespace . '\\' . pathinfo($file, PATHINFO_FILENAME);
            }
        }
        return $controllers;
    }

    /**
     * Extrait les permissions depuis les méthodes publiques d’un contrôleur
     */
    private function extractPermissionsFromController(string $controller, array &$permissions): void
    {
        if (!class_exists($controller)) return;

        $reflection = new ReflectionClass($controller);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $docComment = $method->getDocComment();
            if (!$docComment) continue;

            $permission = $this->extractTagValue($docComment, '@permission');
            $permissionDesc = $this->extractTagValue($docComment, '@permission_desc');

            if ($permission) {
                $permissions[$controller][$method->getName()] = [
                    'permission' => $permission,
                    'permission_desc' => $permissionDesc ?? '',
                ];
            }
        }
    }

    /**
     * Récupère la catégorie définie dans le contrôleur via @permission_category
     */
    private function extractControllerCategory(string $controller): ?string
    {
        $reflection = new ReflectionClass($controller);
        $docComment = $reflection->getDocComment();

        return $docComment ? $this->extractTagValue($docComment, '@permission_category') : null;
    }

    /**
     * Extrait la valeur d’un tag dans un docblock
     */
    private function extractTagValue(string $doc, string $tag): ?string
    {
        return preg_match('/' . preg_quote($tag) . '\s+(.+)/', $doc, $matches)
            ? trim($matches[1])
            : null;
    }
}
