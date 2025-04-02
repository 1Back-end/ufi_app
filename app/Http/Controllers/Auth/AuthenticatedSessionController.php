<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
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

        $permissions = $request->user()->getAllPermissions()->pluck('name')->toArray();

        $access = $request->user()->createToken(
            name: config('app.name'),
            abilities: $permissions,
            expiresAt: now()->addMinutes(config('sanctum.expiration'))
        );

        $user = \auth()->user();

        return \response()->json([
            'access_token' => $access->plainTextToken,
            'expire_in' => $access->accessToken->expires_at,
            'new_user' => $user->default,
            'permissions' => $permissions,
            'user' => $user,
            'centres' => $user->centres()->select(['centres.id', 'centres.name', 'centres.reference'])->get()
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
