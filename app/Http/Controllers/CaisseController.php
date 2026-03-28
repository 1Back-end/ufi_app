<?php

namespace App\Http\Controllers;

use App\Models\Caisse;
use App\Models\Centre;
use App\Models\MouvementCaisse;
use App\Models\SessionCaisse;
use App\Models\SessionElement;
use App\Models\TransfertFonds;
use App\Models\TransfertFondsTampon;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
/**
 * @permission_category Gestion des caisses
 * @permission_module Gestion des caisses
 */
class CaisseController extends Controller
{
    public function changeSecretCode(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'old_secret_code' => 'required|string',
            'new_secret_code' => 'required|string|min:4|confirmed',
        ]);

        $caisse = \App\Models\Caisse::where('user_id', $user->id)->where('is_active', true)->firstOrFail();

        //Si la caisse utilise déjà un code personnalisé
        if ($caisse->is_default_secret_code) {
            return response()->json([
                'message' => __('Le code secret a déjà été personnalisé.')
            ], Response::HTTP_FORBIDDEN);
        }

        //Si une session existe déjà
        $hasSession = SessionCaisse::where('caisse_id', $caisse->id)->exists();
        if ($hasSession) {
            return response()->json([
                'message' => __('Impossible de modifier le code secret : une session caisse existe déjà.')
            ], Response::HTTP_FORBIDDEN);
        }

        //Vérification de l'ancien code
        if (!Hash::check($request->old_secret_code, $caisse->secret_code)) {
            return response()->json([
                'message' => __('Ancien code secret incorrect.')
            ], Response::HTTP_UNAUTHORIZED);
        }

        // ✅ Mise à jour du code secret
        $caisse->update([
            'secret_code' => Hash::make($request->new_secret_code),
            'is_default_secret_code' => true,
            'updated_by' => $user->id,
        ]);

        return response()->json([
            'message' => __('Code secret modifié avec succès.')
        ]);
    }

    /**
     * @return JsonResponse
     *
     * @permission CaisseController::store
     * @permission_desc Créer d'une caisse
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255|unique:caisses',
            'is_active'   => 'nullable|boolean',
            'description' => 'nullable|string|max:255',
            'user_id'     => 'required|exists:users,id',
            'type_caisse' => 'nullable|string|max:255',
            'centre_id'   => 'required|exists:centres,id',
            'is_primary'  => 'nullable|boolean',
            'secret_code' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => __('Données invalides'),
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }


        $existingCaisse = Caisse::where('user_id', $request->user_id)
            ->where('centre_id', $request->centre_id)
            ->first();

        if ($existingCaisse) {
            return response()->json([
                'message' => __(
                    "Cet utilisateur possède déjà une caisse dans ce centre : {$existingCaisse->name}."
                )
            ], Response::HTTP_FORBIDDEN);
        }

        // Vérifier s'il y a déjà une caisse principale dans ce centre
        if ($request->is_primary) {
            $primaryExists = Caisse::where('centre_id', $request->centre_id)
                ->where('is_primary', true)
                ->exists();

            if ($primaryExists) {
                return response()->json([
                    'message' => __("Une caisse principale existe déjà dans ce centre.")
                ], Response::HTTP_FORBIDDEN);
            }
        }

        // Création de la caisse
        $caisse = Caisse::create([
            'name'        => $request->name,
            'description' => $request->description,
            'is_active'   => $request->is_active ?? true,
            'is_primary'  => $request->is_primary ?? false,
            'user_id'     => $request->user_id,
            'centre_id'   => $request->centre_id,
            'type_caisse' => $request->type_caisse,
            'secret_code' => Hash::make($request->secret_code),
            'created_by'  => $auth->id,
            'updated_by'  => $auth->id,
        ]);

        return response()->json([
            'message' => __('Caisse créée avec succès'),
            'data'    => $caisse
        ], Response::HTTP_CREATED);
    }

    /**
     * @return JsonResponse
     *
     * @permission CaisseController::update
     * @permission_desc Modifier une caisse
     */
    public function update(Request $request, string $id)
    {
        $auth = auth()->user();

        $caisse = Caisse::where('id', $id)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255|unique:caisses,name,' . $caisse->id,
            'is_active'   => 'nullable|boolean',
            'description' => 'nullable|string|max:255',
            'user_id'     => 'required|exists:users,id',
            'type_caisse' => 'nullable|string|max:255',
            'centre_id'   => 'required|exists:centres,id',
            'is_primary'  => 'nullable|boolean',
            'secret_code' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => __('Données invalides'),
                'errors'  => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }


        $existingCaisse = Caisse::where('user_id', $request->user_id)
            ->where('centre_id', $request->centre_id)
            ->where('id', '!=', $id)
            ->first();

        if ($existingCaisse) {
            return response()->json([
                'message' => __(
                    "Cet utilisateur possède déjà une caisse dans ce centre : {$existingCaisse->name}."
                )
            ], Response::HTTP_FORBIDDEN);
        }

        // Vérifier s'il y a déjà une caisse principale dans ce centre (sauf la caisse actuelle)
        if ($request->is_primary) {
            $primaryExists = Caisse::where('centre_id', $request->centre_id)
                ->where('is_primary', true)
                ->where('id', '!=', $id)
                ->exists();

            if ($primaryExists) {
                return response()->json([
                    'message' => __("Une caisse principale existe déjà dans ce centre.")
                ], Response::HTTP_FORBIDDEN);
            }
        }

        // Mise à jour de la caisse
        $caisse->update([
            'name'        => $request->name,
            'description' => $request->description,
            'is_active'   => $request->is_active ?? $caisse->is_active,
            'is_primary'  => $request->is_primary ?? $caisse->is_primary,
            'user_id'     => $request->user_id,
            'centre_id'   => $request->centre_id,
            'type_caisse' => $request->type_caisse,
            'secret_code' => Hash::make($request->secret_code),
            'updated_by'  => $auth->id,
        ]);

        return response()->json([
            'message' => __('Caisse mise à jour avec succès'),
            'data'    => $caisse
        ], Response::HTTP_OK);
    }



    /**
     * @return JsonResponse
     *
     * @permission CaisseController::updateStatus
     * @permission_desc Activer/Désactiver une caisse
     */
    public function updateStatus(Request $request, $id)
    {
        $auth     = auth()->user();
        $centreId = $request->header('centre');

        if (!$centreId) {
            return response()->json([
                'message' => __("Vous devez vous connecter à un centre !")
            ], Response::HTTP_UNAUTHORIZED);
        }

        // 🔎 Récupération caisse du centre
        $caisse = Caisse::where('id', $id)
            ->where('centre_id', $centreId)
            ->first();

        if (!$caisse) {
            return response()->json([
                'message' => __('Caisse introuvable')
            ], Response::HTTP_NOT_FOUND);
        }

        // 🔁 Toggle status
        $caisse->update([
            'is_active'  => ! $caisse->is_active,
            'updated_by' => $auth->id,
        ]);

        return response()->json([
            'message' => $caisse->is_active
                ? __('Caisse activée avec succès')
                : __('Caisse désactivée avec succès'),
            'data' => [
                'id'        => $caisse->id,
                'is_active' => $caisse->is_active,
            ]
        ], Response::HTTP_OK);
    }

    /**
     * @return JsonResponse
     *
     * @permission CaisseController::show
     * @permission_desc Afficher les détails d'une caisse
     */
    public function show(Request $request, $id)
    {
        $centreId = $request->header('centre');

        if (!$centreId) {
            return response()->json([
                'message' => __("Vous devez vous connecter à un centre !")
            ], Response::HTTP_UNAUTHORIZED);
        }

        // 🔎 Recherche de la caisse du centre
        $caisse = Caisse::with(['creator', 'updater','centre','user'])
            ->where('id', $id)
            ->where('centre_id', $centreId)
            ->first();

        if (!$caisse) {
            return response()->json([
                'message' => __('Caisse introuvable')
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => __('Caisse récupérée avec succès'),
            'data'    => $caisse
        ], Response::HTTP_OK);
    }



    /**
     * @return JsonResponse
     *
     * @permission CaisseController::destroy
     * @permission_desc Supprimer une caisse
     */
    public function destroy(Request $request, $id)
    {
        $auth     = auth()->user();
        $centreId = $request->header('centre');

        if (!$centreId) {
            return response()->json([
                'message' => __("Vous devez vous connecter à un centre !")
            ], Response::HTTP_UNAUTHORIZED);
        }

        $caisse = Caisse::where('id', $id)
            ->where('centre_id', $centreId)
            ->first();

        if (!$caisse) {
            return response()->json([
                'message' => __('Caisse introuvable')
            ], Response::HTTP_NOT_FOUND);
        }

        // 🚫 Désactivation au lieu de suppression
        $caisse->update([
            'is_active'  => false,
            'updated_by' => $auth->id,
        ]);

        return response()->json([
            'message' => __('Caisse désactivée avec succès')
        ], Response::HTTP_OK);
    }


    /**
     * Display a listing of the resource.
     * @permission CaisseController::index
     * @permission_desc Afficher la liste des caisses
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);
        $centreId = $request->header('centre');

        $query = Caisse::with(['creator', 'updater', 'centre','user'])
            ->where('centre_id', $centreId);


        if ($user->can('view_all_caisses')) {
            Log::info('Permission détectée: view_all_caisses', [
                'user_id' => $user->id,
                'message' => 'L’utilisateur peut voir toutes les caisses'
            ]);

        } elseif ($user->can('view_my_caisses')) {
            Log::info('Permission détectée: view_my_caisses', [
                'user_id' => $user->id,
                'message' => 'L’utilisateur ne voit que ses propres caisses'
            ]);
            $query->where('user_id', $user->id);
        } else {
            Log::warning('Aucune permission pour consulter les caisses', [
                'user_id' => $user->id,
                'message' => 'L’utilisateur n’a pas la permission de consulter les caisses'
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'avez pas la permission de consulter les caisses.'
            ], 403);
        }

        // 🔹 Filtre de recherche
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('solde_caisse', 'like', "%{$search}%");
            });
        }

        // 🔹 Pagination
        $data = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'         => $data->items(),
            'current_page' => $data->currentPage(),
            'last_page'    => $data->lastPage(),
            'total'        => $data->total(),
        ]);
    }


    public function forgot_secret_code_for_my_caisses(Request $request)
    {
        $auth = auth()->user();
        $centreId = $request->header('centre');

        // 1️⃣ Valider le mot de passe du compte
        $request->validate([
            'password' => 'required|string',
        ]);

        $caisse = Caisse::where('centre_id', $centreId)->where('user_id', $auth->id)->latest()->first();

        if (!$caisse) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aucune caisse trouvée pour ce centre.'
            ], 404);
        }

        if (!Hash::check($request->password, $auth->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe incorrect.'
            ], 422);
        }

        \DB::beginTransaction();
        try {
            $caisse->secret_code = \Hash::make($request->password);
            $caisse->is_default_secret_code = false;

            if ($caisse->position === 'open') {
                $caisse->position = 'close';
            }

            $caisse->save();

            // 6️⃣ Fermer la session en cours si elle existe
            $session = SessionCaisse::where('caisse_id', $caisse->id)->where('etat', 'OUVERT')->latest()->first();

            if ($session) {
                $session->etat = 'FERMEE';
                $session->fermeture_ts = now();
                $session->save();
            }

            \DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Le code secret a été réinitialisé et la caisse / session fermée si nécessaire.'
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Erreur réinitialisation code secret caisse', [
                'user_id' => $auth->id,
                'centre_id' => $centreId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue, réessayer plus tard.'
            ], 500);
        }
    }


    public function getConsolidationCaisse(Request $request)
    {
        $perPage  = $request->input('limit', 25);
        $page     = $request->input('page', 1);
        $centreId = $request->header('centre');

        $query = Caisse::with([
            'creator',
            'updater',
            'centre',
            'user',
        ])->where('centre_id', $centreId)
            ->where('type_caisse', 'consolidation_caisse');

        // 🔍 Recherche
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('solde_caisse', 'like', "%{$search}%");
            });
        }

        $data = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'         => $data->items(),
            'current_page' => $data->currentPage(),
            'last_page'    => $data->lastPage(),
            'total'        => $data->total(),
        ]);
    }

    /**
     * @return JsonResponse
     *
     * @permission CaisseController::getBigCaisse
     * @permission_desc Afficher la liste des grandes caisses
     */
    public function getBigCaisse(Request $request)
    {
        $perPage  = $request->input('limit', 25);
        $page     = $request->input('page', 1);
        $centreId = $request->header('centre');

        $query = Caisse::with([
            'creator',
            'updater',
            'centre',
            'user',
        ])->where('centre_id', $centreId)
            ->where('type_caisse', 'small_caisse');

        // 🔍 Recherche
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('solde_caisse', 'like', "%{$search}%");
            });
        }

        $data = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'         => $data->items(),
            'current_page' => $data->currentPage(),
            'last_page'    => $data->lastPage(),
            'total'        => $data->total(),
        ]);
    }


    /**
     * @return JsonResponse
     *
     * @permission CaisseController::getBigCaisseByCentreForMiniTransaction
     * @permission_desc Afficher la liste des caisses de types Consolidations
     */
    public function getBigCaisseByCentreForMiniTransaction(Request $request)
    {
        $perPage  = $request->input('limit', 25);
        $page     = $request->input('page', 1);
        $centreId = $request->header('centre');

        $query = Caisse::with([
            'creator',
            'updater',
            'centre',
            'user',
        ])->where('centre_id', $centreId)
            ->where('type_caisse', 'consolidation_caisse');

        // 🔍 Recherche
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('solde_caisse', 'like', "%{$search}%");
            });
        }

        $data = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'         => $data->items(),
            'current_page' => $data->currentPage(),
            'last_page'    => $data->lastPage(),
            'total'        => $data->total(),
        ]);
    }


    /**
     * @return JsonResponse
     *
     * @permission CaisseController::getBigCaisseByCentre
     * @permission_desc Afficher la liste des caisses des grandes caisses par centre
     */
    public function getBigCaisseByCentre(Request $request)
    {
        $perPage  = $request->input('limit', 25);
        $page     = $request->input('page', 1);

        $query = Caisse::with([
            'creator',
            'updater',
            'centre',
            'user',
        ])->where('type_caisse', 'consolidation_caisse');

        // 🔍 Recherche
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('solde_caisse', 'like', "%{$search}%");
            });
        }

        $data = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'         => $data->items(),
            'current_page' => $data->currentPage(),
            'last_page'    => $data->lastPage(),
            'total'        => $data->total(),
        ]);
    }


    /**
     * @return JsonResponse
     *
     * @permission CaisseController::updatePosition
     * @permission_desc Changer la position des caisses(Ouvrir , Mettre en pause,Fermée)
     */
    public function updatePosition(Request $request, $id)
    {
        $auth = auth()->user();

        // 🔥 sécuriser centre_id
        $centreId = $request->input('centre_id') ?? $request->header('centre');

        if (!$centreId) {
            return response()->json([
                'message' => 'centre_id manquant'
            ], Response::HTTP_BAD_REQUEST);
        }

        $request->validate([
            'position' => 'required|in:open,close,in_pause'
        ]);

        $caisse = Caisse::find($id);
        if (!$caisse) {
            return response()->json([
                'message' => 'Caisse introuvable'
            ], Response::HTTP_NOT_FOUND);
        }

        DB::beginTransaction();

        try {
            $newPosition = $request->position;
            $today = now()->toDateString();
            $alertMessage = null;

            // 🔹 Session du jour
            $session = SessionCaisse::where('user_id', $caisse->user_id)
                ->where('caisse_id', $caisse->id)
                ->where('centre_id', $centreId)
                ->whereDate('ouverture_ts', $today)
                ->whereIn('etat', ['OUVERTE', 'EN_PAUSE'])
                ->latest('ouverture_ts')
                ->first();

            // ============================
            // 🔓 OUVERTURE
            // ============================
            if ($newPosition === 'open') {

                if ($session) {
                    if ($session->etat === 'EN_PAUSE') {
                        $session->update([
                            'etat'       => 'OUVERTE',
                            'pause_ts'   => null,
                            'updated_by' => $auth->id,
                        ]);
                    } else {
                        return response()->json([
                            'message' => "Une session est déjà ouverte aujourd'hui."
                        ], Response::HTTP_FORBIDDEN);
                    }
                } else {

                    $lastSession = SessionCaisse::where('caisse_id', $caisse->id)
                        ->where('user_id', $caisse->user_id)
                        ->where('centre_id', $centreId)
                        ->where('etat', 'FERMEE')
                        ->latest('fermeture_ts')
                        ->first();

                    $fondsOuverture = $lastSession?->fonds_fermeture ?? 0;

                    if ($fondsOuverture > 0) {
                        $alertMessage = "⚠️ Solde reporté : "
                            . number_format($fondsOuverture, 0, ',', ' ') . " FCFA";
                    }

                    $session = SessionCaisse::create([
                        'caisse_id'       => $caisse->id,
                        'user_id'         => $caisse->user_id,
                        'centre_id'       => $centreId,
                        'ouverture_ts'    => now(),
                        'fonds_ouverture' => $fondsOuverture,
                        'solde'           => $fondsOuverture,
                        'etat'            => 'OUVERTE',
                        'created_by'      => $auth->id,
                        'updated_by'      => $auth->id,
                    ]);
                }
            }

            // ============================
            // ⏸️ PAUSE
            // ============================
            if ($newPosition === 'in_pause') {

                if (!$session) {
                    return response()->json([
                        'message' => 'Aucune session trouvée.'
                    ], 404);
                }

                // 🔥 Si déjà en pause → on bloque proprement
                if ($session->etat === 'EN_PAUSE') {
                    return response()->json([
                        'message' => 'La session est déjà en pause.'
                    ], 400);
                }

                // 🔥 Autoriser uniquement si ouverte
                if ($session->etat !== 'OUVERTE') {
                    return response()->json([
                        'message' => 'Impossible de mettre en pause une session non ouverte.'
                    ], 400);
                }

                // ✅ Mise en pause
                $session->update([
                    'etat'           => 'EN_PAUSE',
                    'pause_ts'       => now(),
                    'fonds_en_pause' => $session->fonds_ouverture + $session->solde,
                    'updated_by'     => $auth->id,
                ]);
            }

            // ============================
            // 🔒 FERMETURE
            // ============================
            if ($newPosition === 'close') {

                if (!$session) {
                    return response()->json([
                        'message' => 'Aucune session à fermer.'
                    ], Response::HTTP_NOT_FOUND);
                }

                $total = $session->fonds_ouverture + $session->solde;

                $session->update([
                    'fermeture_ts'              => now(),
                    'etat'                      => 'FERMEE',
                    'fonds_fermeture'           => $total,
                    'fonds_fermeture_exactly'   => $total,
                    'solde'                     => $total,
                    'updated_by'                => $auth->id,
                ]);
            }

            // 🔄 Update caisse
            $caisse->update([
                'position'   => $newPosition,
                'updated_by' => $auth->id
            ]);

            DB::commit();

            return response()->json([
                'message'     => "Caisse en position '{$newPosition}'",
                'session_id'  => $session?->id,
                'alert'       => $alertMessage
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur serveur',
                'error'   => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    /**
     * @return JsonResponse
     *
     * @permission CaisseController::myCaisseStatus
     * @permission_desc Etat de la caisse
     */
    public function myCaisseStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        $centreId = $request->header('centre');

        $caisse = Caisse::where('user_id', $user->id)
            ->where('centre_id', $centreId)
            ->where('is_active', true)
            ->first();

        if (!$caisse) {
            return response()->json([
                'status'  => 'not_found',
                'message' => 'Aucune caisse assignée à cet utilisateur pour ce centre.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'caisse_id'    => $caisse->id,
                'name'         => $caisse->name,
                'is_active'    => $caisse->is_active,
                'is_primary'   => $caisse->is_primary,
                'type_caisse'  => $caisse->type_caisse,
                'status'       => $caisse->position,
                'centre_id'    => $caisse->centre_id,
            ]
        ], 200);
    }


    /**
     * @return JsonResponse
     *
     * @permission CaisseController::pauseMyCaisse
     * @permission_desc Mettre la caisse en pause
     */
    public function pauseMyCaisse(Request $request)
    {
        $auth = auth()->user();
        $centreId = $request->header('centre');

        // 🔹 Vérifier la caisse ouverte
        $caisse = Caisse::where('user_id', $auth->id)
            ->where('centre_id', $centreId)
            ->where('position', 'open')
            ->first();

        if (!$caisse) {
            return response()->json([
                'message' => 'Aucune caisse ouverte à mettre en pause.'
            ], 404);
        }

        // 🔹 Envoyer les bonnes données
        $request->merge([
            'position' => 'in_pause',
            'centre_id' => $centreId
        ]);

        return $this->updatePosition($request, $caisse->id);
    }


    /**
     * @return JsonResponse
     *
     * @permission CaisseController::OpenMyCaisse
     * @permission_desc Réouvrir la caisse
     */
    public function OpenMyCaisse(Request $request)
    {
        $auth = auth()->user();
        $centreId = $request->header('centre');

        $request->validate([
            'secret_code' => ['required', 'string'],
        ], [
            'secret_code.required' => "Le code secret est obligatoire",
            'secret_code.string' => "Le code secret doit être une chaîne de caractères",
        ]);

        // 🔹 Chercher la caisse active de l'utilisateur pour ce centre
        $caisse = Caisse::where('user_id', $auth->id)
            ->where('centre_id', $centreId)
            ->where('is_active', true)
            ->first();

        if (!$caisse) {
            return response()->json([
                'message' => 'Aucune caisse active trouvée pour cet utilisateur dans ce centre.'
            ], Response::HTTP_FORBIDDEN);
        }

        // 🔹 Vérifier si le code secret est personnalisé
        if (!$caisse->is_default_secret_code) {
            return response()->json([
                'message' => 'Vous devez d’abord changer le code secret avant d’ouvrir la caisse.'
            ], Response::HTTP_FORBIDDEN);
        }

        // 🔹 Vérification du code secret
        if (!Hash::check($request->secret_code, $caisse->secret_code)) {
            return response()->json([
                'message' => 'Code secret incorrect.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // 🔹 Vérifier si une session ouverte existe pour cette caisse + centre
        $session = SessionCaisse::where('caisse_id', $caisse->id)
            ->where('user_id', $auth->id)
            ->where('centre_id', $centreId)
            ->whereNull('fermeture_ts')
            ->first();

        if ($session) {
            // 🔹 Mettre à jour l'état de la session si elle est en pause
            if ($session->etat === 'PAUSE') {
                $session->update([
                    'etat' => 'OUVERTE',
                    'updated_by' => $auth->id
                ]);
            }

            // 🔹 Mettre à jour la caisse
            $caisse->update([
                'position' => 'open'
            ]);

            return response()->json([
                'message' => 'Caisse réouverte avec succès pour ce centre.',
                'session' => $session
            ], Response::HTTP_OK);
        }

        $request->merge(['position' => 'open', 'centre_id' => $centreId]);
        return $this->updatePosition($request, $caisse->id);
    }


    public function CloseMyCaisse(Request $request)
    {
        $auth = auth()->user();
        $centreId = $request->header('centre'); // 🔹 Centre spécifique

        $request->validate([
            'secret_code' => ['required', 'string'],
        ], [
            'secret_code.required' => "Le code secret est obligatoire",
            'secret_code.string' => "Le code secret doit être une chaîne de caractères",
        ]);

        // 🔹 Chercher la caisse active de l'utilisateur pour ce centre
        $caisse = Caisse::where('user_id', $auth->id)
            ->where('centre_id', $centreId)
            ->where('is_active', true)
            ->first();

        if (!$caisse) {
            return response()->json([
                'message' => 'Aucune caisse active trouvée pour cet utilisateur dans ce centre.'
            ], Response::HTTP_FORBIDDEN);
        }

        // 🔹 Vérifier le code secret avant fermeture
        if (!Hash::check($request->secret_code, $caisse->secret_code)) {
            return response()->json([
                'message' => 'Code secret incorrect.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // 🔹 Récupérer la session ouverte ou en pause pour ce centre
        $session = SessionCaisse::where('caisse_id', $caisse->id)
            ->where('user_id', $auth->id)
            ->where('centre_id', $centreId)
            ->whereNull('fermeture_ts')
            ->first();

        if (!$session) {
            return response()->json([
                'message' => 'Aucune session ouverte trouvée pour cette caisse dans ce centre.'
            ], Response::HTTP_FORBIDDEN);
        }

        // 🔹 Vérifier le solde restant de la session
        if ($session->solde > 0) {
            return response()->json([
                'message' => 'Vous devez transférer les fonds restants avant de fermer la caisse.'
            ], Response::HTTP_FORBIDDEN);
        }

        // 🔹 Vérifier si la caisse est déjà fermée
        if ($caisse->position === 'close') {
            return response()->json([
                'message' => 'La caisse est déjà fermée pour ce centre.'
            ], Response::HTTP_OK);
        }

        DB::beginTransaction();
        try {
            // 🔹 Mettre à jour la caisse pour ce centre
            $caisse->update([
                'position'   => 'close',
                'updated_by' => $auth->id
            ]);

            // 🔹 Mettre à jour la session correspondante
            $session->update([
                'etat'        => 'FERMEE',
                'fermeture_ts'=> now(),
                'updated_by'  => $auth->id
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Caisse fermée avec succès pour ce centre.',
                'session' => $session
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur serveur lors de la fermeture de la caisse.',
                'error'   => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    /**
     * @return JsonResponse
     *
     * @permission CaisseController::initTransfer
     * @permission_desc Transférer les fonds de la caisse
     */
    public function initTransfer(Request $request)
    {
        $auth = auth()->user();
        $centreId = $request->header('centre'); // 🔹 Centre courant

        if (!$centreId) {
            return response()->json([
                'message' => 'Vous devez spécifier un centre pour effectuer le transfert.'
            ], 403);
        }

        // 🔹 Validation de la requête
        $validated = $request->validate([
            'caisse_reception_id' => ['required', 'integer'],
            'montant' => ['required', 'integer', 'min:1'],
        ]);

        // 🔹 Récupérer la session active de l'utilisateur dans ce centre
        $session = SessionCaisse::where('user_id', $auth->id)
            ->where('centre_id', $centreId)
            ->whereNull('fermeture_ts')
            ->where('etat', 'OUVERTE')
            ->first();

        if (!$session) {
            return response()->json([
                'message' => 'Aucune session de caisse active dans ce centre.'
            ], 403);
        }

        // 🔹 Vérifier la caisse de réception
        $caisseReception = Caisse::where('id', $validated['caisse_reception_id'])
            ->where('is_active', true)
            ->where('centre_id', $centreId) // 🔹 Vérifie le centre
            ->first();

        if (!$caisseReception) {
            return response()->json([
                'message' => 'Caisse de réception invalide pour ce centre.'
            ], 404);
        }

        // 🔹 Créer le transfert temporaire
        $transfert = TransfertFondsTampon::create([
            'caisse_depart_id' => $session->caisse_id,
            'caisse_reception_id' => $caisseReception->id,
            'montant_send' => $validated['montant'],
            'status' => 'pending',
            'type' => 'debit',
            'send_by' => $auth->id,
            'session_id' => $session->id,
            'centre_id' => $centreId,
            'created_by' => $auth->id,
        ]);

        // 🔹 Fermer la session
        $session->update([
            'etat' => 'FERMEE',
            'fermeture_ts' => now(),
            'fonds_fermeture' => $session->solde,
            'fonds_fermeture_exactly' => $session->solde,
            'solde' => $session->solde,
            'updated_by' => $auth->id,
        ]);

        // 🔹 Fermer la caisse
        $caisse = Caisse::find($session->caisse_id);
        $caisse->update([
            'position' => 'close',
        ]);

        return response()->json([
            'message' => 'Transfert initié avec succès. La caisse et la session sont maintenant fermées.',
            'transfert' => $transfert,
        ], 201);
    }

    /**
     * @return JsonResponse
     *
     * @permission CaisseController::validateTransfer
     * @permission_desc Valider le transfert les fonds de la caisse
     */
    public function validateTransfer(Request $request,string $id)
    {

        $auth = auth()->user();

        $request->validate([
            'password' => 'required|string'
        ]);

        if (!Hash::check($request->password, $auth->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Mot de passe incorrect.'
            ], 422);
        }

        $transfert = TransfertFondsTampon::where('id', $id)
            ->where('status', 'pending')
            ->first();

        if (!$transfert) {
            return response()->json([
                'message' => 'Transfert introuvable ou déjà traité'
            ], 404);
        }

        // 🔎 Dernière session de la caisse de départ (même fermée)
        $session = SessionCaisse::where('caisse_id', $transfert->caisse_depart_id)
            ->latest('created_at')
            ->first();

        if (!$session) {
            return response()->json([
                'message' => 'Aucune session de caisse trouvée pour la validation'
            ], 403);
        }

        if ($session->solde < $transfert->montant_send) {
            return response()->json([
                'message' => 'Solde insuffisant dans la session sélectionnée'
            ], 403);
        }

        $caisseDepart = $session->caisse;
        $caisseReception = Caisse::find($transfert->caisse_reception_id);

        if (!$caisseReception) {
            return response()->json([
                'message' => 'Caisse de réception introuvable'
            ], 404);
        }

        DB::beginTransaction();

        try {

            // 🔻 Débit de la session (même si fermée)
            $session->solde -= $transfert->montant_send;
            $session->solde = max(0, $session->solde);
            $session->save();

            if (!is_null($session->fonds_fermeture)) {
                $session->fonds_fermeture -= $transfert->montant_send;
                $session->fonds_fermeture = max(0, $session->fonds_fermeture);
            }

            $session->save();

            $caisseDepart->solde_caisse -= $transfert->montant_send;
            $caisseDepart->solde_caisse = max(0, $caisseDepart->solde_caisse);
            $caisseDepart->save();

            $caisseReception->solde_caisse += $transfert->montant_send;
            $caisseReception->save();

            $transfert->update([
                'status'       => 'validated',
                'validated_by' => $auth->id,
                'validated_at' => now(),
                'session_id'   => $session->id, // on garde la session liée
            ]);

            // 🔹 Créer le transfert officiel
            TransfertFonds::create([
                'code' => $transfert->code,
                'caisse_depart_id' => $transfert->caisse_depart_id,
                'caisse_reception_id' => $transfert->caisse_reception_id,
                'montant_send' => $transfert->montant_send,
                'status' => 'validated',
                'type' => 'debit',
                'send_by' => $transfert->send_by,
                'validated_by' => $auth->id,
                'centre_id' => $transfert->centre_id,
                'created_by' => $auth->id,
            ]);

            MouvementCaisse::create([
                'type' => 'transfert',
                'caisse_depart_id' => $transfert->caisse_depart_id,
                'caisse_arrivee_id' => $transfert->caisse_reception_id,
                'montant' => $transfert->montant_send,
                'description' => "Transfert validé: {$transfert->code}",
                'status' => 'validated',
                'created_by' => $auth->id,
                'updated_by' => $auth->id,
                'centre_id' => $transfert->centre_id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Transfert validé avec succès'
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la validation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @return JsonResponse
     *
     * @permission CaisseController::rejectTransfer
     * @permission_desc Rejetter le transfert les fonds de la caisse
     */
    public function rejectTransfer(Request $request, string $id)
    {
        $auth = auth()->user();

        // 🔹 Validation
        $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $transfert = TransfertFondsTampon::where('id', $id)
            ->where('status', 'pending')
            ->first();

        if (!$transfert) {
            return response()->json([
                'message' => 'Transfert introuvable ou déjà traité'
            ], 404);
        }

        DB::beginTransaction();

        try {

            $transfert->update([
                'status'        => 'cancelled',
                'reason'        => $request->reason,
                'rejected_by'  => $auth->id,
                'rejected_at'  => now(),
                'updated_by'    => $auth->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Transfert annulé avec succès'
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de l’annulation',
                'error'   => $e->getMessage()
            ], 500);
        }
    }



    public function change_secret_code(Request $request)
    {
        $user = $request->user();
        $centreId = $request->header('centre');

        // 🔹 Validation de la requête
        $request->validate([
            'old_secret_code' => ['required', 'string'],
            'new_secret_code' => ['required', 'string'],
        ], [
            'old_secret_code.required' => "L'ancien code secret est obligatoire",
            'new_secret_code.required' => "Le nouveau code secret est obligatoire",
        ]);

        // 🔹 Récupère la caisse active de l'utilisateur pour ce centre
        $caisse = Caisse::where('user_id', $user->id)
            ->where('centre_id', $centreId)
            ->where('is_active', true)
            ->first();

        if (!$caisse) {
            return response()->json([
                'status'  => 'not_found',
                'message' => 'Aucune caisse assignée à cet utilisateur pour ce centre.'
            ], 404);
        }


        if (!Hash::check($request->old_secret_code, $caisse->secret_code)) {
            return response()->json([
                'message' => __('Ancien code secret incorrect.')
            ], Response::HTTP_UNAUTHORIZED);
        }

        // 🔹 Récupère les sessions ouvertes pour cette caisse
        $sessions = SessionCaisse::where('caisse_id', $caisse->id)
            ->whereNull('fermeture_ts') // sessions encore ouvertes
            ->get();

        foreach ($sessions as $session) {
            $session->update([
                'fermeture_ts' => now(),
                'etat'         => 'FERMEE',
                'updated_by'   => $user->id,
            ]);
        }
        // 🔹 Mise à jour du code secret de la caisse
        $caisse->update([
            'secret_code' => Hash::make($request->new_secret_code),
            'is_default_secret_code' => true,
            'updated_by' => $user->id,
        ]);

        $caisse->update([
            'position' => 'close',
            'updated_by' => $user->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => __('Code secret modifié avec succès.')
        ], 200);
    }


    public function print_data_caisses_by_centre(Request $request)
    {
        try {

            $centreId = $request->header('centre');

            if (!$centreId) {
                return response()->json([
                    'message' => 'Centre non fourni'
                ], 400);
            }

            $caisseId = $request->input('caisse_id');
            if (!$caisseId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Caisse non sélectionné.'
                ], 422);
            }

            $start_date = \Illuminate\Support\Carbon::parse($request->input('start_date'))->startOfDay();
            $end_date = \Illuminate\Support\Carbon::parse($request->input('end_date'))->endOfDay();

            // 🔹 Query propre
            $query = SessionElement::with([ 'centre', 'creator', 'updater', 'facture', 'caisse', 'regulation_method' ])
                ->where('centre_id', $centreId) ->where('caisse_id', $request->caisse_id)
                ->whereBetween('created_at', [$start_date, $end_date]);

            $result = $query->orderBy('created_at', 'ASC')->get();

            if ($result->isEmpty()) {
                return response()->json([
                    'message' => 'Aucune donnée trouvée.'
                ], 404);
            }


            $centre = Centre::find($centreId);
            $media = $centre?->medias()->where('name', 'logo')->first();

            $data = [
                'result' => $result,
                'logo' => $media ? 'storage/' . $media->path . '/' . $media->filename : '',
                'centre' => $centre,
                'start' => $start_date,
                'end' => $end_date
            ];

            // 🔹 Génération PDF
            $fileName = 'etats-caisses-' . now()->format('YmdHis') . '.pdf';
            $folderPath = 'storage/etats-caisses';
            $filePath = $folderPath . '/' . $fileName;

            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0755, true);
            }

            save_browser_shot_pdf(
                view: 'pdfs.etats-caisses.etats-caisses',
                data: $data,
                folderPath: $folderPath,
                path: $filePath,
                margins: [15, 10, 15, 10],
                footer: 'pdfs.reports.factures.footer',
                format: 'A4',
                direction: 'landscape'
            );

            if (!file_exists($filePath)) {
                return response()->json([
                    'message' => 'Le fichier PDF n\'a pas été généré.'
                ], 500);
            }

            // 🔹 Encodage
            $pdfContent = file_get_contents($filePath);
            $base64 = base64_encode($pdfContent);

            return response()->json([
                'result' => $result,
                'base64' => $base64,
                'url' => $filePath,
                'filename' => $fileName,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'error' => 'Erreur de validation',
                'messages' => $e->errors()
            ], 422);

        } catch (\Exception $e) {

            return response()->json([
                'error'   => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }










    //
}
