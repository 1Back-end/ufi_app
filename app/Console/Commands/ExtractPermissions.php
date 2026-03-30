<?php

namespace App\Console\Commands;

use App\Models\CategoryPermission;
use App\Models\Permission;
use App\Models\PermissionCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use ReflectionClass;
use ReflectionMethod;

class ExtractPermissions extends Command
{
    protected $signature = 'permissions:extract';
    protected $description = 'Synchronise les permissions avec les annotations des contrôleurs et les catégorise par menu.';

    protected string $defaultManualModule = 'Autres Modules';
    protected array $manualPermissions = [
        'view_access_for_caisses' => [
            'description' => 'Afficher le menu principal de gestion des caisses',
            'category' => 'Permissions supplémentaires',
            'modules' => ['Gestion des caisses','Autres Modules'],
        ],
        'view_access_for_prestations_and_facturations' => [
            'description' => 'Afficher le menu principal de gestion des prestations et des facturations',
            'category' => 'Permissions supplémentaires',
            'modules' => ['Gestion des prestations','Autres Modules'],
        ],
        'view_access_for_first_settings_of_systems' => [
            'description' => 'Afficher le menu principal des paramètres applicatifs',
            'category' => 'Permissions supplémentaires',
            'modules' => ['Paramètres Applicatifs','Autres Modules'],
        ],
        'view_access_for_first_settings_of_facturations' => [
            'description' => 'Afficher le menu principal des paramètres de facturations',
            'category' => 'Permissions supplémentaires',
            'modules' => ['Paramètres Facturations','Autres Modules'],
        ],
        'view_access_for_first_settings_of_laboratoire' => [
            'description' => 'Afficher le menu principal de saisie des résultats du laboratoire',
            'category' => 'Permissions supplémentaires',
            'modules' => ['Gestion du laboratoire','Autres Modules'],
        ],
        'view_access_for_assurance' => [
            'description' => 'Afficher le menu principal des assurances',
            'category' => 'Permissions supplémentaires',
            'modules' => ['Gestion des prestations','Autres Modules'],
        ],
        'view_access_for_g_stocks' => [
            'description' => 'Afficher le menu principal de gestions des stocks',
            'category' => 'Permissions supplémentaires',
            'modules' => ['Gestion des stocks','Autres Modules'],
        ],
        'actions_caisses_users' => [
            'description' => 'Gérer les actions des utilisateurs sur les caisses',
            'category' => 'Permissions supplémentaires',
            'modules' => ['Gestion des caisses','Autres Modules'],
        ],
        'view_all_caisses' => [
            'description' => 'Accéder à la liste de toute les caisses',
            'category' => 'Permissions supplémentaires',
            'modules' => ['Gestion des caisses','Autres Modules'],
        ],
        'view_my_caisses' => [
            'description' => 'Donner la possibilité à un utilisateur de voir ces caisses',
            'category' => 'Permissions supplémentaires',
            'modules' => ['Gestion des caisses','Autres Modules'],
        ],
        'view_all_sessions_caisses' => [
            'description' => 'Accéder à toutes les sessions de caisses par centre',
            'category' => 'Permissions supplémentaires',
            'modules' => ['Gestion des caisses','Autres Modules'],
        ],
        'view_my_sessions_caisses' => [
            'description' => 'Donner la possibilité à un utilisateur de voir ces sessions de caisses',
            'category' => 'Permissions supplémentaires',
            'modules' => ['Gestion des caisses','Autres Modules'],
        ],
        'view_all_transferts' => [
            'description' => 'Accéder à tous mes transferts de caisses',
            'category' => 'Permissions supplémentaires',
            'modules' => ['Gestion des caisses','Autres Modules'],
        ],
        'view_my_transferts' => [
            'description' => 'Donner la possiblité à un utilisateur de voir ces transferts de caisses',
            'category' => 'Permissions supplémentaires',
            'modules' => ['Gestion des caisses','Autres Modules'],
        ],
        'ActionClient' => [
            'description' => 'Accéder à toute les actions des clients',
            'category' => 'Permissions supplémentaires',
            'modules' => ['Gestion des prestations','Autres Modules'],
        ],
        'Authorizations' => [
            'description' => 'Paramètres d\'autorisations' ,
            'category' => 'Permissions supplémentaires',
            'modules' => ['Paramètres Applicatifs','Gestion des prestations','Paramètres Facturations','Autres Modules','Gestion des caisses','Gestion des stocks','Gestion du laboratoire'],
        ],
        'ReportAccess' => [
            'description' => 'Afficher le menu principal des rapports',
            'category' => 'Permissions supplémentaires',
            'modules' => ['Gestion des prestations','Autres Modules'],
        ],
        'ConfigurationsRendezVous' => [
            'description' => 'Afficher le menu principal des rendez vous',
            'category' => 'Permissions supplémentaires',
            'modules' => ['Gestion des prestations','Autres Modules'],
        ],

        'ELC' => [
            'description' => 'Afficher le menu principal des éléments courants',
            'category' => 'Permissions supplémentaires',
            'modules' => ['Paramètres Applicatifs','Autres Modules'],
        ],
        'view_access_for_first_config_of_laboratoire' => [
            'description' => 'Afficher le menu principal des configurations du laboratoire',
            'category' => 'Permissions supplémentaires',
            'modules' => ['Gestion du laboratoire','Autres Modules'],
        ],





    ];

    public function handle(): void
    {
        $controllersPath = app_path('Http/Controllers');
        $permissions = [];

        $systemUser = User::where('login', 'SYSTEM')->first();
        $systemId = $systemUser?->id ?? 1;
        $superAdminRole = Role::find(1);

        // 1️⃣ Extraction
        foreach ($this->getControllers($controllersPath) as $controller) {
            $this->extractPermissionsFromController($controller, $permissions);
        }

        $this->info("\n--- Synchronisation des permissions et modules ---");

        $validPermissions = [];

        // 🔁 Fonction interne pour éviter duplication
        $syncPermission = function ($name, $data) use (&$validPermissions, $systemId, $superAdminRole) {

            $categoryName = $data['category'] ?? 'Autres';
            $modules = $data['modules'] ?? [$this->defaultManualModule];

            // ✅ Catégorie
            $category = PermissionCategory::firstOrCreate(
                ['name' => $categoryName],
                [
                    'description' => $categoryName,
                    'created_by' => $systemId,
                    'updated_by' => $systemId,
                ]
            );

            // ✅ Permission
            $permission = Permission::updateOrCreate(
                ['name' => $name],
                [
                    'description' => $data['description'] ?? '',
                    'category_id' => $category->id,
                    'system' => true,
                    'active' => true,
                    'created_by' => $systemId,
                    'updated_by' => $systemId,
                ]
            );

            $validPermissions[] = $permission->name;

            // 🔥 Synchronisation des modules (IMPORTANT)
            $moduleIds = [];

            foreach ($modules as $moduleName) {
                $moduleSlug = \Str::slug($moduleName);

                $module = \App\Models\ModuleApplications::firstOrCreate(
                    ['slug' => $moduleSlug],
                    [
                        'name' => $moduleName,
                        'description' => $moduleName,
                        'is_active' => true,
                        'created_by' => $systemId,
                        'updated_by' => $systemId,
                    ]
                );

                $this->info("📦 Module synchronisé : {$module->name}");

                $moduleIds[] = $module->id;
            }

            // 🔥 MAGIC : ajoute + supprime anciens modules
            $permission->modules()->sync($moduleIds);

            // Optionnel
            $permission->module_id = $moduleIds[0] ?? null;
            $permission->save();

            // ✅ Super admin
            if ($superAdminRole && !$superAdminRole->permissions()->where('permission_id', $permission->id)->exists()) {
                $superAdminRole->permissions()->attach($permission->id, [
                    'created_by' => $systemId,
                    'updated_by' => $systemId,
                ]);
            }

            $this->info("✅ Permission synchronisée : {$permission->name}");
        };

        // ----------------------- Permissions contrôleurs -----------------------
        foreach ($permissions as $controller => $methods) {
            ksort($methods);
            usort($methods, fn($a, $b) => strcmp($a['permission'], $b['permission']));

            foreach ($methods as $perm) {
                $syncPermission($perm['permission'], [
                    'description' => $perm['permission_desc'] ?? '',
                    'category' => $perm['category'] ?? 'Autres',
                    'modules' => $perm['modules'] ?? [$this->defaultManualModule],
                ]);
            }
        }

        // ----------------------- Permissions manuelles -----------------------
        foreach ($this->manualPermissions as $name => $data) {
            $syncPermission($name, $data);
        }

        // ----------------------- Nettoyage -----------------------

        // ❌ Permissions supprimées
        Permission::where('system', true)
            ->whereNotIn('name', $validPermissions)
            ->get()
            ->each(function ($permission) {
                $permission->modules()->detach(); // important
                $permission->delete();
                $this->warn("🗑️ Permission supprimée : {$permission->name}");
            });

        // ❌ Catégories inutilisées
        $usedCategoryIds = Permission::pluck('category_id')->unique()->filter();
        PermissionCategory::whereNotIn('id', $usedCategoryIds)->each(function ($category) {
            $category->delete();
            $this->warn("🗑️ Catégorie supprimée : {$category->name}");
        });

        // ❌ Modules sans permissions
        \App\Models\ModuleApplications::doesntHave('permissions')
            ->get()
            ->each(function ($module) {
                $module->delete();
                $this->warn("🗑️ Module supprimé : {$module->name}");
            });

        $this->info("\n✅ Synchronisation complète terminée !");
    }

    /**
     * Extraction des permissions depuis un contrôleur avec catégorie et modules multiples
     */
    private function extractPermissionsFromController(string $controller, array &$permissions): void
    {
        if (!class_exists($controller)) return;

        $reflection = new \ReflectionClass($controller);

        // Docblock du contrôleur
        $doc = $reflection->getDocComment() ?: '';

        // Récupère la catégorie (une seule)
        $category = $this->extractTagValue($doc, '@permission_category') ?: 'Autres';

        // Récupère tous les modules (peut être plusieurs)
        $modules = $this->extractTagValues($doc, '@permission_module');
        if (empty($modules)) {
            $modules = ['Autres Modules']; // fallback
        }

        // Parcours des méthodes publiques
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $docComment = $method->getDocComment();
            if (!$docComment) continue;

            $permission     = $this->extractTagValue($docComment, '@permission');
            $permissionDesc = $this->extractTagValue($docComment, '@permission_desc') ?? '';

            if ($permission) {
                $permissions[$controller][$method->getName()] = [
                    'permission'      => $permission,
                    'permission_desc' => $permissionDesc,
                    'category'        => $category,
                    'modules'         => $modules, // tableau de modules
                ];
            }
        }
    }

    /**
     * Récupère toutes les valeurs d'un tag dans un docblock
     * Permet de gérer plusieurs modules
     */
    private function extractTagValues(string $doc, string $tag): array
    {
        preg_match_all('/' . preg_quote($tag, '/') . '\s+(.+)/', $doc, $matches);
        return isset($matches[1]) ? array_map('trim', $matches[1]) : [];
    }

    /**
     * Récupère tous les contrôleurs d'un répertoire récursivement
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
     * Récupère la catégorie définie dans le contrôleur via @permission_category
     */
    private function extractControllerCategory(string $controller): ?string
    {
        $reflection = new \ReflectionClass($controller);
        return $reflection->getDocComment()
            ? $this->extractTagValue($reflection->getDocComment(), '@permission_category')
            : null;
    }

    /**
     * Extrait une seule valeur d’un tag dans un docblock
     */
    private function extractTagValue(string $doc, string $tag): ?string
    {
        return preg_match('/' . preg_quote($tag, '/') . '\s+(.+)/', $doc, $matches)
            ? trim($matches[1])
            : null;
    }
}
