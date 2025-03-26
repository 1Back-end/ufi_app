<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ReflectionClass;
use ReflectionMethod;

class CheckPermission
{
    public function handle(Request $request, Closure $next)
    {
        $controller = $request->route()->getControllerClass();
        $method = $request->route()->getActionMethod();

        $requiredPermissions = $this->getMethodPermissions($controller, $method);

        if (!empty($requiredPermissions) && !$this->userHasPermission($requiredPermissions)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $next($request);
    }

    private function getMethodPermissions($controller, $method): array
    {
        if (!class_exists($controller)) {
            return [];
        }

        $reflection = new ReflectionClass($controller);
        if (!$reflection->hasMethod($method)) {
            return [];
        }

        $method = $reflection->getMethod($method);
        $docComment = $method->getDocComment();

        return $docComment ? $this->extractTagValues($docComment) : [];
    }

    private function extractTagValues($doc): array
    {
        preg_match_all('/' . preg_quote('@permission') . '\s+(.+)/', $doc, $matches);
        return array_map('trim', $matches[1] ?? []);
    }

    private function userHasPermission($requiredPermissions): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        // Supposons que l'utilisateur ait une méthode hasPermission($perm)
        foreach ($requiredPermissions as $permission) {
            $hasPermission = !$user->permissions()->where(function (Builder $query) use($permission) {
                $query->where('permissions.name', $permission)
                    ->where('permissions.active', true);
            })->wherePivot('active', true)->exists();

            $hasPermissionByRole = !$user->roles()
                ->where('roles.active', true)
                ->whereHas('permissions', function (Builder $query) use($permission) {
                    $query->where('permissions.name', $permission)
                    ->where('permissions.active', true);
            })->wherePivot('active', true)
                ->exists();

            if ($hasPermission || $hasPermissionByRole) {
                return false;
            }
        }

        return true;
    }
}
