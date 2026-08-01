<?php

namespace App\Http\Controllers;

use App\Enums\StockAdjustmentAction;
use App\Models\StocksRegularisation;
use App\Models\StocksRegularisationsItem;
use App\Models\TransfertStock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * @permission_category Gestion des régularisations de stocks
 * @permission_module Gestion des stocks
 * @permission_module Gestion des prestations
 */

class StocksRegularisationController extends Controller
{

    /**
     * Display a listing of the resource.
     * @permission StocksRegularisationController::index
     * @permission_desc Afficher la liste des régularisations de stocks
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page    = $request->input('page', 1);

        $query = StocksRegularisation::with([
            'emplacement:id,zone_stockage',
            'items.product:id,ref,name',
            'items.packaging:id,numero_lot_fabricant',
            'creator:id,nom_utilisateur',
            'updater:id,nom_utilisateur',
            'validator:id,nom_utilisateur',
        ]);

        if ($request->filled('emplacement_id')) {
            $query->where('emplacement_id', $request->emplacement_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate   = Carbon::parse($request->input('end_date'))->endOfDay();

            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        if ($search = trim($request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('status', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")

                    ->orWhereHas('emplacement', function ($qs) use ($search) {
                        $qs->where('zone_stockage', 'like', "%{$search}%")
                            ->orWhere('equipement', 'like', "%{$search}%")
                            ->orWhere('position_detaillee', 'like', "%{$search}%");
                    })

                    ->orWhereHas('items.product', function ($qp) use ($search) {
                        $qp->where('name', 'like', "%{$search}%")
                            ->orWhere('ref', 'like', "%{$search}%")
                            ->orWhere('generic_name', 'like', "%{$search}%");
                    });
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
     * Display a listing of the resource.
     * @permission StocksRegularisationController::store
     * @permission_desc Créer une  régularisation de stocks
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|string',
            'action' => 'required|string',
            'emplacement_id' => 'nullable|exists:emplacements_products,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantite' => 'required|integer|min:1',
            'items.*.packaging_id' => 'nullable|exists:lot_produits,id',
            'items.*.status' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $userId = $request->user()?->id;

            $regularisation = StocksRegularisation::create([
                'status' => $request->input('status', 'pending'),
                'action' => $request->input('action'),
                'emplacement_id' => $request->input('emplacement_id'),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach ($request->input('items') as $itemData) {
                StocksRegularisationsItem::create([
                    'stocks_regularisation_id' => $regularisation->id,
                    'product_id' => $itemData['product_id'],
                    'quantite' => $itemData['quantite'],
                    'packaging_id' => $itemData['packaging_id'] ?? null,
                    'status' => $itemData['status'] ?? 'pending',
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }

            DB::commit();

            $regularisation->load(['items.product', 'items.packaging', 'emplacement', 'creator']);

            return response()->json([
                'message' => 'Régularisation de stock enregistrée avec succès.',
                'data' => $regularisation
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de l\'enregistrement de la régularisation.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission StocksRegularisationController::update
     * @permission_desc Modifier une régularisation de stocks
     */
    public function update(Request $request, $id)
    {
        $regularisation = StocksRegularisation::find($id);

        if (!$regularisation) {
            return response()->json([
                'message' => 'Régularisation de stock non trouvée.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|string',
            'action' => 'required|string',
            'emplacement_id' => 'nullable|exists:emplacements_products,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantite' => 'required|integer|min:1',
            'items.*.packaging_id' => 'nullable|exists:lot_produits,id',
            'items.*.status' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $userId = $request->user()?->id;

            $regularisation->update([
                'status' => $request->input('status', $regularisation->status),
                'action' => $request->input('action'),
                'emplacement_id' => $request->input('emplacement_id'),
                'updated_by' => $userId,
            ]);


            $regularisation->items()->delete();

            foreach ($request->input('items') as $itemData) {
                StocksRegularisationsItem::create([
                    'stocks_regularisation_id' => $regularisation->id,
                    'product_id' => $itemData['product_id'],
                    'quantite' => $itemData['quantite'],
                    'packaging_id' => $itemData['packaging_id'] ?? null,
                    'status' => $itemData['status'] ?? 'pending',
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }

            DB::commit();

            $regularisation->load(['items.product', 'items.packaging', 'emplacement', 'creator', 'updater']);

            return response()->json([
                'message' => 'Régularisation de stock mise à jour avec succès.',
                'data' => $regularisation
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la mise à jour de la régularisation.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission StocksRegularisationController::show
     * @permission_desc Afficher les détails d'une régularisation de stocks
     */
    public function show($id)
    {
        $regularisation = StocksRegularisation::with([
            'emplacement:id,zone_stockage',
            'items.product:id,ref,name',
            'items.packaging:id,numero_lot_fabricant',
            'creator:id,nom_utilisateur',
            'updater:id,nom_utilisateur',
            'validator:id,nom_utilisateur',
        ])->find($id);

        if (!$regularisation) {
            return response()->json([
                'message' => 'Régularisation de stock non trouvée.'
            ], 404);
        }

        return response()->json([
            'message' => 'Détails de la régularisation de stock récupérés avec succès.',
            'data' => $regularisation
        ], 200);
    }

    /**
     * Display a listing of the resource.
     * @permission StocksRegularisationController::validate
     * @permission_desc Valider une régularisation de stocks
     */
    public function validate(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!$user || !\Illuminate\Support\Facades\Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'message' => 'Mot de passe incorrect.',
                'errors' => [
                    'password' => ['Le mot de passe fourni est incorrect.']
                ]
            ], 422);
        }

        $regularisation = StocksRegularisation::with('items')->find($id);

        if (!$regularisation) {
            return response()->json([
                'message' => 'Régularisation de stock non trouvée.'
            ], 404);
        }

        try {
            DB::beginTransaction();

            $userId = $user->id;
            $action = $regularisation->action;

            foreach ($regularisation->items as $item) {
                if ($item->packaging_id) {
                    $lot = \App\Models\LotProduit::where('id', $item->packaging_id)
                        ->where('id_emplacement', $regularisation->emplacement_id)
                        ->first();

                    if ($lot) {
                        $actionValue = is_object($action) ? $action->value : $action;

                        if ($actionValue === StockAdjustmentAction::AJUSTEMENT_PLUS->value) {
                            $lot->increment('quantite_actuelle', $item->quantite);
                        } elseif (in_array($actionValue, [
                            StockAdjustmentAction::AJUSTEMENT_MOINS->value,
                            StockAdjustmentAction::AVARIE->value
                        ])) {
                            $lot->decrement('quantite_actuelle', $item->quantite);
                        }
                    }
                }
            }

            $regularisation->update([
                'status' => 'validated',
                'updated_by' => $userId,
                'validated_at' => now(),
                'validated_by' => $userId,
            ]);

            $regularisation->items()->update([
                'status' => 'validated',
                'updated_by' => $userId,
            ]);

            DB::commit();

            $regularisation->load(['items.product', 'items.packaging', 'emplacement', 'creator', 'updater']);

            return response()->json([
                'message' => 'Régularisation de stock validée avec succès.',
                'data' => $regularisation
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la validation de la régularisation.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
