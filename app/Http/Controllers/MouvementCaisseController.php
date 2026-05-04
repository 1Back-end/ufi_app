<?php

namespace App\Http\Controllers;

use App\Models\Caisse;
use App\Models\MouvementCaisse;
use App\Models\TransfertFonds;
use App\Models\TransfertFondsTampon;
use App\Models\TransfertGrandeCaisse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * @permission_category Gestion des mouvements de caisses
 * @permission_module Gestion des caisses
 */
class MouvementCaisseController extends Controller
{

    /**
     * @return JsonResponse
     *
     * @permission MouvementCaisseController::store
     * @permission_desc Créer un transfert entre les caisses(Grandes=>Petites)
     */
    public function store(Request $request)
    {
        $auth = auth()->user();
        $centreId = $request->header('centre'); // 🔹 Centre courant

        if (!$centreId) {
            return response()->json([
                'message' => 'Vous devez spécifier un centre pour effectuer le transfert.'
            ], 403);
        }

        $validated = $request->validate([
            'type' => ['required', 'in:add,remove'],
            'caisse_depart_id' => ['nullable', 'exists:caisses,id'],
            'caisse_arrivee_id' => ['nullable', 'exists:caisses,id'],
            'montant' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ]);

        $caisseDepart = $validated['caisse_depart_id']
            ? \App\Models\Caisse::find($validated['caisse_depart_id'])
            : null;

        DB::beginTransaction();

        try {
            $mouvement = \App\Models\MouvementCaisse::create([
                'type' => $validated['type'],
                'caisse_depart_id' => $validated['caisse_depart_id'] ?? null,
                'caisse_arrivee_id' => $validated['caisse_arrivee_id'] ?? null,
                'montant' => $validated['montant'],
                'description' => $validated['description'] ?? null,
                'status' => 'pending',
                'centre_id' => $centreId,
                'created_by' => $auth->id,
                'updated_by' => $auth->id,
            ]);

            \App\Models\TransfertFondsTampon::create([
                'caisse_depart_id' => $validated['caisse_depart_id'] ?? null,
                'caisse_reception_id' => $validated['caisse_arrivee_id'] ?? null,
                'session_id' => null,
                'status' => 'pending',
                'montant_send' => $validated['montant'],
                'send_by' => $caisseDepart?->user_id ?? $auth->id, // fallback à l'utilisateur courant
                'centre_id' => $caisseDepart?->centre_id ?? $centreId, // fallback au centre courant
                'type' => $validated['type'],
                'created_by' => $auth->id,
                'updated_by' => $auth->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Mouvement caisse et transfert tampon créés avec succès',
                'mouvement' => $mouvement
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la création du mouvement',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @return JsonResponse
     *
     * @permission MouvementCaisseController::store
     * @permission_desc Modifier un transfert entre les caisses(Grandes=>Petites)
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();
        $centreId = $request->header('centre'); // 🔹 Centre courant

        if (!$centreId) {
            return response()->json([
                'message' => 'Vous devez spécifier un centre pour effectuer le transfert.'
            ], 403);
        }

        // 🔹 Validation des champs
        $validated = $request->validate([
            'type' => ['required', 'in:add,remove'],
            'caisse_depart_id' => ['nullable', 'exists:caisses,id'],
            'caisse_arrivee_id' => ['nullable', 'exists:caisses,id'],
            'montant' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ]);

        // 🔹 Récupérer le mouvement
        $mouvement = \App\Models\MouvementCaisse::find($id);
        if (!$mouvement) {
            return response()->json([
                'message' => 'Mouvement caisse introuvable.'
            ], 404);
        }

        $caisseDepart = $validated['caisse_depart_id']
            ? \App\Models\Caisse::find($validated['caisse_depart_id'])
            : null;

        DB::beginTransaction();

        try {
            // 🔹 Mise à jour du mouvement
            $mouvement->update([
                'type' => $validated['type'],
                'caisse_depart_id' => $validated['caisse_depart_id'] ?? null,
                'caisse_arrivee_id' => $validated['caisse_arrivee_id'] ?? null,
                'montant' => $validated['montant'],
                'description' => $validated['description'] ?? null,
                'centre_id' => $centreId,
                'updated_by' => $auth->id,
            ]);

            // 🔹 Mise à jour du transfert tampon associé si exists
            $transfert = \App\Models\TransfertFondsTampon::where('caisse_depart_id', $mouvement->caisse_depart_id)
                ->where('caisse_reception_id', $mouvement->caisse_arrivee_id)
                ->where('status', 'pending')
                ->latest('created_at')
                ->first();

            if ($transfert) {
                $transfert->update([
                    'caisse_depart_id' => $validated['caisse_depart_id'] ?? null,
                    'caisse_reception_id' => $validated['caisse_arrivee_id'] ?? null,
                    'montant_send' => $validated['montant'],
                    'send_by' => $caisseDepart?->user_id ?? $auth->id,
                    'centre_id' => $caisseDepart?->centre_id ?? $centreId,
                    'type' => $validated['type'],
                    'updated_by' => $auth->id,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Mouvement caisse et transfert tampon mis à jour avec succès',
                'mouvement' => $mouvement,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la mise à jour du mouvement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @return JsonResponse
     *
     * @permission MouvementCaisseController::store_transaction_between_big_caisses
     * @permission_desc Créer un transfert de caisses par centre (Grande Caisse)
     */
    public function store_transaction_between_big_caisses(Request $request)
    {
        $auth = auth()->user();
        $centreId = $request->header('centre');

        if (!$centreId) {
            return response()->json([
                'message' => 'Vous devez spécifier un centre pour effectuer le transfert.'
            ], 403);
        }

        $validated = $request->validate([
            'caisse_depart_id' => ['required', 'exists:caisses,id'],
            'caisse_arrivee_id' => ['required', 'exists:caisses,id'],
            'montant' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'montant_type' => ['required', 'in:small_change,other'],
        ]);

        DB::beginTransaction();

        try {

            $caisseDepart = \App\Models\Caisse::findOrFail($validated['caisse_depart_id']);
            $caisseArrivee = \App\Models\Caisse::findOrFail($validated['caisse_arrivee_id']);

            if ($validated['caisse_depart_id'] == $validated['caisse_arrivee_id']) {
                return response()->json([
                    'message' => 'La caisse de départ et la caisse d\'arrivée doivent être différentes.'
                ], 422);
            }

            if ($caisseDepart->solde_caisse < $validated['montant']) {
                return response()->json([
                    'message' => 'Solde insuffisant dans la caisse de départ.'
                ], 403);
            }

            $transfert = \App\Models\TransfertGrandeCaisse::create([
                'caisse_depart_id' => $validated['caisse_depart_id'],
                'caisse_reception_id' => $validated['caisse_arrivee_id'],
                'session_id' => null,
                'status' => 'pending',
                'type' => 'add',
                'montant' => $validated['montant'],
                'send_by' => $auth->id,
                'centre_id' => $centreId,
                'description' => $validated['description'] ?? null,
                'created_by' => $auth->id,
                'updated_by' => $auth->id,
            ]);

            if ($validated['montant_type'] === 'small_change') {
                $caisseDepart->decrement('solde_caisse', $validated['montant']);
                $caisseArrivee->increment('small_change', $validated['montant']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Transfert enregistré et en attente de validation',
                'transfert' => $transfert
            ], 201);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la création du transfert',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @return JsonResponse
     *
     * @permission MouvementCaisseController::validateTransferBetweenBigCaisses
     * @permission_desc Valider un transfert de caisses par centre (Grande Caisses)
     */
    public function validateTransferBetweenBigCaisses(Request $request, $id)
    {
        $auth = auth()->user();

        $request->validate([
            'password' => 'required|string'
        ]);

        // Vérification mot de passe
        if (!Hash::check($request->password, $auth->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mot de passe incorrect'
            ], 422);
        }

        $transfert = TransfertGrandeCaisse::where('id', $id)
            ->where('status', 'pending')
            ->first();

        if (!$transfert) {
            return response()->json([
                'message' => 'Transfert introuvable ou déjà validé'
            ], 404);
        }

        DB::beginTransaction();

        try {

            $caisseDepart = Caisse::findOrFail($transfert->caisse_depart_id);
            $caisseArrivee = Caisse::findOrFail($transfert->caisse_reception_id);

            // Vérification solde
            if ($caisseDepart->solde_caisse < $transfert->montant) {
                return response()->json([
                    'message' => 'Solde insuffisant dans la caisse de départ'
                ], 403);
            }

            // 🔻 Déduire caisse départ
            $caisseDepart->solde_caisse -= $transfert->montant;
            $caisseDepart->save();

            // 🔺 Ajouter caisse arrivée
            $caisseArrivee->solde_caisse += $transfert->montant;
            $caisseArrivee->save();

            // 🔹 Création mouvement caisse
            MouvementCaisse::create([
                'type' => 'transfer',
                'caisse_depart_id' => $transfert->caisse_depart_id,
                'caisse_arrivee_id' => $transfert->caisse_reception_id,
                'montant' => $transfert->montant,
                'description' => 'Transfert validé ' .$transfert->code,
                'status' => 'validated',
                'created_by' => $auth->id,
                'updated_by' => $auth->id,
                'centre_id' => $transfert->centre_id
            ]);

            TransfertFonds::create([
                'code' => $transfert->code,
                'caisse_depart_id' => $transfert->caisse_depart_id,
                'caisse_reception_id' => $transfert->caisse_reception_id,
                'montant_send' => $transfert->montant,
                'status' => 'validated',
                'type' => 'transfer',
                'send_by' => $transfert->send_by,
                'validated_by' => $auth->id,
                'centre_id' => $transfert->centre_id,
                'created_by' => $auth->id,
            ]);

            // 🔹 Mise à jour du transfert
            $transfert->update([
                'status' => 'validated',
                'validated_by' => $auth->id,
                'validated_at' => now()
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
     * @permission MouvementCaisseController::rejectTransferBetweenBigCaisses
     * @permission_desc Rejetter un transfert de caisses par centre (Grande Caisses)
     */
    public function rejectTransferBetweenBigCaisses(Request $request, $id)
    {
        $auth = auth()->user();

        $request->validate([
            'reason_for_rejection' => 'required|string|max:255'
        ]);

        $transfert = TransfertGrandeCaisse::where('id', $id)
            ->where('status', 'pending')
            ->first();

        if (!$transfert) {
            return response()->json([
                'message' => 'Transfert introuvable ou déjà traité'
            ], 404);
        }

        DB::beginTransaction();

        try {
            // Mise à jour du transfert pour rejection
            $transfert->update([
                'status' => 'cancelled',
                'rejected_by' => $auth->id,
                'rejected_at' => now(),
                'reason_for_rejection' => $request->reason_for_rejection
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Transfert annulé avec succès'
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de l\'annulation du transfert',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * @return JsonResponse
     *
     * @permission MouvementCaisseController::get_transfert_big_caisse_virtuel
     * @permission_desc Afficher la liste des transferts de caisses par centre (Grande Caisses)
     */
    public function get_transfert_big_caisse_virtuel(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);
        $user = $request->user();
        $centreId = $request->header('centre');

        $query = TransfertGrandeCaisse::with([
            'creator', 'updater', 'centre', 'caisse_depart', 'caisse_reception', 'sender'
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
                    ->orWhere('montant', 'like', "%$search%");
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
