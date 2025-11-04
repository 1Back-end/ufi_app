<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Centre;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();
        $user = $request->user();
        if (!$user->status) {
            return response()->json([
                "status" => "error",
                "message" => "Votre compte est désactivé !"
            ], \Symfony\Component\HttpFoundation\Response::HTTP_UNAUTHORIZED);
        }

        $centre = $user->centres()->select(['centres.id', 'centres.name', 'centres.reference'])->wherePivot('default', true)->first();

        if (!$centre && \auth()->user()->hasRole('Super Admin')) {
            $centres = Centre::select(['centres.id', 'centres.name', 'centres.name_alias', 'centres.reference'])->get();
            $centre = $centres->first();
        } else {
            $centres = $user->centres()->select(['centres.id', 'centres.name', 'centres.name_alias', 'centres.reference'])->get();
        }

        $permissions = load_permissions($user, $centre);

        $access = $request->user()->createToken(
            name: config('app.name'),
            abilities: $permissions,
            expiresAt: now()->addMinutes(config('sanctum.expiration'))
        );

        $user->increment('connexion_counter');

        return \response()->json([
            'access_token' => $access->plainTextToken,
            'expire_in' => $access->accessToken->expires_at,
            'new_user' => $user->default,
            'permissions' => $permissions,
            'centres' => $centres,
            'centre_default' => $centre,
            'user' => $user
        ]);
    }

    /**
     * @param Centre $centre
     * @return JsonResponse
     */
    public function getPermissionByCenter(Centre $centre)
    {
        return response()->json([
            'permissions' => load_permissions(\auth()->user(), $centre)
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
