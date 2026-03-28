<?php

namespace App\Http\Controllers;

use App\Models\Caisse;
use App\Models\SessionCaisse;
use App\Models\TransfertFonds;
use App\Models\TransfertFondsTampon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        // 🔹 Gestion des permissions
        if ($user->can('view_all_sessions_caisses')) {
            Log::info("User {$user->id} voit toutes les sessions du centre {$centreId}");
            // pas de restriction
        } elseif ($user->can('view_my_sessions_caisses')) {
            // 🔹 Ne récupérer que les sessions démarrées par l'utilisateur
            $query->where('user_id', $user->id);
            Log::info("User {$user->id} voit uniquement ses propres sessions dans le centre {$centreId}");
        } else {
            Log::warning("User {$user->id} n'a pas la permission de voir les sessions");
            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'avez pas la permission de consulter les sessions.'
            ], 403);
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
        $query = TransfertFonds::with([
            'creator',
            'updater',
            'centre',
            'caisse_depart',
            'caisse_reception',
            'sender',
            'centre',
            'validated'
        ])
            ->where('centre_id', $request->header('centre'))
            ->where('status', 'validated');


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

        $data = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $data->items(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'total' => $data->total(),
        ]);

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
            ->where('centre_id', $centreId);

        // --- LOGIQUE DES PERMISSIONS ET LOGS ---
        if (!$user->can('view_all_transferts')) {
            if ($user->can('view_my_transferts')) {
                $query->where('created_by', $user->id);
            } else {
                return response()->json(['data' => [], 'total' => 0], 403);
            }
        } else {

        }

        // --- LOG RECHERCHE ---
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



}
