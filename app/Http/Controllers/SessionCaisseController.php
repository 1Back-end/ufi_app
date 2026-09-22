<?php

namespace App\Http\Controllers;

use App\Models\Caisse;
use App\Models\SessionCaisse;
use App\Models\TransfertFonds;
use App\Models\TransfertFondsTampon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
/**
 * @permission_category Gestion des sessions caisses
 * @permission_module Gestion des caisses
 */
class SessionCaisseController extends Controller
{

    /**
     * @return JsonResponse
     *
     * @permission SessionCaisseController::index
     * @permission_desc Afficher la liste des sessions caisses
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);
        $centreId = $request->header('centre');

        if (!$centreId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Le centre n\'est pas défini.'
            ], 400);
        }

        $query = SessionCaisse::with([
            'creator',
            'updator',
            'centre',
            'utilisateur',
            'caisse'
        ])->where('centre_id', $centreId);

        if ($user->can('view_all_sessions_caisses')) {
            Log::info("User {$user->id} voit toutes les sessions du centre {$centreId}");
        } elseif ($user->can('view_my_sessions_caisses')) {
            $query->where('user_id', $user->id);
            Log::info("User {$user->id} voit uniquement ses propres sessions dans le centre {$centreId}");
        } else {
            Log::warning("User {$user->id} n'a pas la permission de voir les sessions");
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'avez pas la permission de consulter les sessions.'
            ], 403);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();

            $query->whereBetween('created_at', [$startDate, $endDate]);
        } else {
            $query->whereBetween('created_at', [
                Carbon::today()->subDay()->startOfDay(),
                Carbon::today()->addDay()->endOfDay()
            ]);
        }

        // 🔹 FILTRE RECHERCHE
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('etat', 'like', "%$search%")
                    ->orWhere('id', 'like', "%$search%")
                    ->orWhere('solde', 'like', "%$search%");
            });
        }

        try {
            $data = $query->latest()->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'data' => $data->items(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'total' => $data->total(),
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur lors de la récupération des sessions (User {$user->id}) : " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors du chargement des sessions.'
            ], 500);
        }
    }


    /**
     * @return JsonResponse
     *
     * @permission SessionCaisseController::show
     * @permission_desc Afficher les détails d'une session caisse
     */
    public function show(Request $request, $id)
    {
        $auth = $request->user();
        $centreId = $request->header('centre');

        if (!$centreId) {
            return response()->json([
                'message' => __("Vous devez vous connecter à un centre !")
            ], Response::HTTP_UNAUTHORIZED);
        }

        $query = SessionCaisse::with([
            'utilisateur',
            'centre',
            'caisse',
            'creator',
            'updator'
        ])->where('id', $id)
            ->where('centre_id', $centreId);

        // 🔹 Gestion permissions
        if ($auth->can('view_all_sessions_caisses')) {
            // accès complet, rien à filtrer
        } elseif ($auth->can('view_my_sessions_caisses')) {
            // accès limité aux caisses de l'utilisateur dans ce centre
            $caisseIds = Caisse::where('user_id', $auth->id)
                ->where('centre_id', $centreId)
                ->pluck('id');

            $query->whereIn('caisse_id', $caisseIds);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'avez pas la permission de consulter cette session.'
            ], 403);
        }

        $session_caisse = $query->first();

        if (!$session_caisse) {
            return response()->json([
                'message' => __('Session caisse introuvable')
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => __('Session Caisse récupérée avec succès'),
            'session_caisse' => $session_caisse
        ], Response::HTTP_OK);
    }



    /**
     * @return JsonResponse
     *
     * @permission SessionCaisseController::get_transfert_caisse
     * @permission_desc Afficher la liste des transferts de caisses
     */
    public function get_transfert_caisse(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);
        $centreId = $request->header('centre');

        if (!$centreId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Le centre n\'est pas défini.'
            ], 400);
        }

        $query = TransfertFonds::with([
            'creator',
            'updater',
            'centre',
            'caisse_depart',
            'caisse_reception',
            'sender',
            'validated'
        ])
            ->where('centre_id', $centreId)
            ->where('status', 'validated');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();

            $query->whereBetween('created_at', [$startDate, $endDate]);
        } else {
            $query->whereBetween('created_at', [
                Carbon::today()->startOfDay(),
                Carbon::today()->addDays(2)->endOfDay()
            ]);
        }

        // 🔹 RECHERCHE
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('status', 'like', "%$search%")
                    ->orWhere('code', 'like', "%$search%")
                    ->orWhere('type', 'like', "%$search%")
                    ->orWhere('id', 'like', "%$search%")
                    ->orWhere('montant_send', 'like', "%$search%");
            });
        }

        try {
            $data = $query->latest()->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'data'         => $data->items(),
                'current_page' => $data->currentPage(),
                'last_page'    => $data->lastPage(),
                'total'        => $data->total(),
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur lors de la récupération des transferts de caisse (Centre {$centreId}) : " . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Une erreur est survenue lors du chargement des données.'
            ], 500);
        }
    }


    /**
     * @return JsonResponse
     *
     * @permission SessionCaisseController::get_transfert_caisse_virtuel
     * @permission_desc Afficher la liste des transferts de caisses virtuels
     */
    public function get_transfert_caisse_virtuel(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);
        $user = $request->user();
        $centreId = $request->header('centre');

        $query = TransfertFondsTampon::with([
                'creator', 'updater', 'centre', 'caisse_depart', 'caisse_reception', 'sender','session'
        ])
            ->where('centre_id', $centreId)
            ->where('status', '!=', 'validated');

        if (!$user->can('view_all_transferts')) {
            if ($user->can('view_my_transferts')) {
                $query->where('created_by', $user->id);
            } else {
                return response()->json(['data' => [], 'total' => 0], 403);
            }
        } else {

        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            Log::debug("Recherche effectuée par l'utilisateur {$user->id} : '{$search}'");

            $query->where(function ($q) use ($search) {
                $q->where('status', 'like', "%$search%")
                    ->orWhere('code', 'like', "%$search%")
                    ->orWhere('type', 'like', "%$search%")
                    ->orWhere('id', 'like', "%$search%")
                    ->orWhere('montant_send', 'like', "%$search%");
            });
        }

        try {
            $data = $query->latest()->paginate($perPage, ['*'], 'page', $page);
            return response()->json([
                'data' => $data->items(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'total' => $data->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Une erreur est survenue lors du chargement des données.'], 500);
        }
    }



    /**
     * @return JsonResponse
     *
     * @permission SessionCaisseController::get_all_my_transferts
     * @permission_desc Afficher la liste des transferts de caisses d'un utilisateur
     */
    public function get_all_my_transferts(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);
        $user = $request->user();
        $centreId = $request->header('centre');

        $query = TransfertFondsTampon::with([
            'creator', 'updater', 'centre', 'caisse_depart', 'caisse_reception', 'sender', 'session'
        ])
            ->where('centre_id', $centreId)
            ->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhere('send_by', $user->id);
            });

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date'))->endOfDay();

            $query->whereBetween('created_at', [$startDate, $endDate]);
        } else {
            $query->whereBetween('created_at', [
                Carbon::today()->subDay()->startOfDay(),
                Carbon::today()->addDay()->endOfDay()
            ]);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            Log::debug("Recherche effectuée par l'utilisateur {$user->id} : '{$search}'");

            $query->where(function ($q) use ($search) {
                $q->where('status', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhere('montant_send', 'like', "%{$search}%");
            });
        }

        try {
            $data = $query->latest()->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'data'         => $data->items(),
                'current_page' => $data->currentPage(),
                'last_page'    => $data->lastPage(),
                'total'        => $data->total(),
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur chargement mes transferts (User {$user->id}): " . $e->getMessage());

            return response()->json([
                'error' => 'Une erreur est survenue lors du chargement des données.'
            ], 500);
        }
    }


    /**
     * @return JsonResponse
     *
     * @permission SessionCaisseController::retransfer
     * @permission_desc Relancer et réémettre les transferts de fonds rejetés sélectionnés
     */
    public function retransfer(Request $request)
    {
        $auth = auth()->user();
        $centreId = $request->header('centre');

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:transfert_fonds_tampons,id'],
            'password' => ['required', 'string'],
        ], [
            'ids.required' => "Aucun transfert sélectionné.",
            'password.required' => "Veuillez entrer votre mot de passe pour confirmer.",
        ]);

        if (!Hash::check($request->password, $auth->password)) {
            return response()->json([
                'message' => "Mot de passe incorrect. Action non autorisée."
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $oldTransferts = TransfertFondsTampon::whereIn('id', $validated['ids'])
                ->where('status', 'cancelled')
                ->where('centre_id', $centreId)
                ->get();

            if ($oldTransferts->isEmpty()) {
                return response()->json([
                    'message' => "Aucun transfert rejeté valide trouvé pour cette opération."
                ], Response::HTTP_NOT_FOUND);
            }

            $newTransfersCount = 0;

            foreach ($oldTransferts as $old) {
                $dateRejet = $old->rejected_at ? date('d/m/Y à H:i', strtotime($old->rejected_at)) : '';
                $reasonMessage = "Relance de transfert consécutive au rejet de la référence {$old->code} en date du {$dateRejet}";
                TransfertFondsTampon::create([
                    'caisse_depart_id' => $old->caisse_depart_id,
                    'caisse_reception_id' => $old->caisse_reception_id,
                    'session_id' => $old->session_id,
                    'montant_send' => $old->montant_send,
                    'small_change' => $old->small_change,
                    'type' => $old->type,
                    'status' => 'pending',
                    'centre_id' => $old->centre_id,
                    'send_by' => $auth->id,
                    'created_by' => $auth->id,
                    'updated_by' => $auth->id,
                    'transfer_date' => now(),
                    'transferred_by' => $auth->id,
                    'reason_of_transfer' => $reasonMessage,
                    'is_retransferred' => true
                ]);

                $old->update([
                    'status' => 'archived',
                    'updated_by' => $auth->id
                ]);

                $newTransfersCount++;
            }

            return response()->json([
                'message' => "Les nouveaux transferts ont été créés avec succès à partir des éléments rejetés.",
                'count' => $newTransfersCount
            ], Response::HTTP_OK);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => "Une erreur est survenue lors de la recréation des transferts : " . $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



}
