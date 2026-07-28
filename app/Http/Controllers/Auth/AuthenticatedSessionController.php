<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Caisse;
use App\Models\Centre;
use App\Models\SessionCaisse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        $caisse = \App\Models\Caisse::where('user_id', $user->id)->where('is_active', true)->first();
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
            'user' => $user,
            'caisse'         => $caisse,
        ]);
    }

    /**
     * @param Centre $centre
     * @return JsonResponse
     */
    public function getPermissionByCenter(Centre $centre)
    {
        $caisse = $centre->caisses()
            ->where('is_active', true)
            ->get(['id', 'code', 'name', 'solde_caisse', 'type_caisse']);

        return response()->json([
            'permissions' => load_permissions(\auth()->user(), $centre),
            'caisse'     => $caisse,
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

    public function refresh(Request $request)
    {
        $user = $request->user();

        if (!$user->status) {
            return response()->json([
                "status" => "error",
                "message" => "Votre compte est désactivé !"
            ], 401);
        }

        $permissions = load_permissions($user);

        // Recharge les rôles
        $roles = $user->roles()->pluck('name');

        return response()->json([
            'status' => 'success',
            'permissions' => $permissions,
            'roles' => $roles,
            'user' => $user,
        ]);
    }

    public function getSystemConfig()
    {
        $auth = auth()->user();

        if ($auth) {
            $sessions = SessionCaisse::where('user_id', $auth->id)
                ->where('etat', 'OUVERTE')
                ->whereNull('fermeture_ts')
                ->get();

            if ($sessions->isNotEmpty()) {
                $caisseIds = $sessions->pluck('caisse_id')->unique();

                Caisse::whereIn('id', $caisseIds)->update([
                    'position'   => 'in_pause',
                    'updated_by' => $auth->id
                ]);

                foreach ($sessions as $session) {
                    $session->update([
                        'etat'           => 'EN_PAUSE',
                        'pause_ts'       => now(),
                        'fonds_en_pause' => $session->solde,
                        'updated_by'     => $auth->id
                    ]);
                }
            }
        }

        $timeout = DB::table('settings')
            ->where('key', 'inactivity_timeout_minutes')
            ->value('value') ?? 30;

        return response()->json([
            'inactivity_timeout' => (int) $timeout
        ], Response::HTTP_OK);
    }
}
