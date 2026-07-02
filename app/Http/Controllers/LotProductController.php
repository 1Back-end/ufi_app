<?php

namespace App\Http\Controllers;

use App\Models\LotProduit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * @permission_category Gestion des lots de produits
 * @permission_module Gestion des stocks
 */

class LotProductController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission LotProductController::index
     * @permission_desc Afficher la liste des lots de  produits
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = LotProduit::with(["creator", "updater", "produit","emplacement"]);

        if ($search = trim($request->input('search'))) {
            $query->where(function ($query) use ($search) {

                $query->where('id', 'like', "%{$search}%")
                    ->orWhere('numero_lot_fabricant', 'like', "%{$search}%")
                    ->orWhere('date_peremption', 'like', "%{$search}%")
                    ->orWhere('date_reception', 'like', "%{$search}%")
                    ->orWhere('quantite_actuelle', 'like', "%{$search}%")
                    ->orWhere('statut', 'like', "%{$search}%")

                    ->orWhereHas('produit', function ($q) use ($search) {
                        $q->where('ref', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%")
                            ->orWhere('generic_name', 'like', "%{$search}%")
                            ->orWhere('manufacturer_reference', 'like', "%{$search}%")
                            ->orWhere('product_type', 'like', "%{$search}%")
                            ->orWhere('dosage', 'like', "%{$search}%")
                            ->orWhere('laboratory_family', 'like', "%{$search}%")
                            ->orWhere('storage_unit', 'like', "%{$search}%")
                            ->orWhere('consumption_unit', 'like', "%{$search}%")
                            ->orWhere('conversion_factor', 'like', "%{$search}%")
                            ->orWhere('alert_threshold', 'like', "%{$search}%")
                            ->orWhere('minimum_threshold', 'like', "%{$search}%")
                            ->orWhere('storage_temperature', 'like', "%{$search}%")
                            ->orWhere('purchase_price', 'like', "%{$search}%")
                            ->orWhere('price', 'like', "%{$search}%")
                            ->orWhere('facturable', 'like', "%{$search}%");
                    })

                    ->orWhereHas('emplacement', function ($q) use ($search) {
                        $q->where('zone_stockage', 'like', "%{$search}%")
                            ->orWhere('equipement', 'like', "%{$search}%")
                            ->orWhere('position_detaillee', 'like', "%{$search}%")
                            ->orWhere('is_active', 'like', "%{$search}%");
                    });
            });
        }

        $lots = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $lots->items(),
            'current_page' => $lots->currentPage(),
            'last_page' => $lots->lastPage(),
            'total' => $lots->total(),
        ]);
    }


    /**
     * Display a listing of the resource.
     * @permission LotProductController::store
     * @permission_desc Créer un lot de produits
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        $validator = Validator::make($request->all(), [
            'numero_lot_fabricant' => [
                'required',
                'string',
                'max:255',
                'unique:lot_produits,numero_lot_fabricant',
            ],
            'date_peremption'   => 'required|date|after:today',
            'date_reception'    => 'required|date',
            'quantite_actuelle' => 'required|integer|min:0',
            'id_produit'        => 'required|exists:products,id',
            'id_emplacement'    => 'required|exists:emplacements_products,id',
        ], [
            'numero_lot_fabricant.unique' => 'Ce numéro de lot existe déjà dans le système.',
            'id_produit.exists'           => 'Le produit sélectionné est invalide.',
            'id_emplacement.exists'       => 'L\'emplacement sélectionné est invalide.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Les données sont invalides.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $lot = LotProduit::create([
                'numero_lot_fabricant' => $request->numero_lot_fabricant,
                'date_peremption'      => $request->date_peremption,
                'date_reception'       => $request->date_reception,
                'quantite_actuelle'    => $request->quantite_actuelle,
                'id_produit'           => $request->id_produit,
                'id_emplacement'       => $request->id_emplacement,
                'created_by'           => $auth->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lot enregistré avec succès.',
                'data'    => $lot,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'enregistrement.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Display a listing of the resource.
     * @permission LotProductController::update
     * @permission_desc Modifier un lot de produits
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();

        $lot = LotProduit::find($id);
        if (!$lot) {
            return response()->json([
                'success' => false,
                'message' => 'Lot introuvable.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'numero_lot_fabricant' => [
                'required',
                'string',
                'max:255',
                Rule::unique('lot_produits', 'numero_lot_fabricant')->ignore($lot->id),
            ],
            'date_peremption'   => 'required|date|after:today',
            'date_reception'    => 'required|date',
            'quantite_actuelle' => 'required|integer|min:0',
            'id_produit'        => 'required|exists:products,id',
            'id_emplacement'    => 'required|exists:emplacements_products,id',
        ], [
            'numero_lot_fabricant.unique' => 'Ce numéro de lot existe déjà dans le système.',
            'id_produit.exists'           => 'Le produit sélectionné est invalide.',
            'id_emplacement.exists'       => 'L\'emplacement sélectionné est invalide.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Les données sont invalides.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $lot->update([
                'numero_lot_fabricant' => $request->numero_lot_fabricant,
                'date_peremption'      => $request->date_peremption,
                'date_reception'       => $request->date_reception,
                'quantite_actuelle'    => $request->quantite_actuelle,
                'id_produit'           => $request->id_produit,
                'id_emplacement'       => $request->id_emplacement,
                'updated_by'           => $auth->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lot mis à jour avec succès.',
                'data'    => $lot,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la mise à jour.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission LotProductController::show
     * @permission_desc Afficher les détails d'un lot de produits
     */
    public function show(string $id)
    {
        try {
            $query = LotProduit::with(["creator", "updater", "produit","emplacement"])->findOrFail($id);

            return response()->json([
                'data' => $query,
                'message' => 'Détails du lot récupérés avec succès.'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Lot non trouvé.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur est survenue.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
