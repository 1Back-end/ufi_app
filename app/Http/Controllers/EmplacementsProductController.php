<?php

namespace App\Http\Controllers;

use App\Models\EmplacementsProduct;
use Illuminate\Http\Request;
/**
 * @permission_category Gestion des emplacements des produits
 * @permission_module Gestion des stocks
 */
class EmplacementsProductController extends Controller
{


    /**
     * Display a listing of the resource.
     * @permission EmplacementsProductController::index
     * @permission_desc Afficher la liste des emplacements des produits
     */
    public function index(Request $request)
    {
        $auth = auth()->user();
        $perPage = $request->input('limit', 5);
        $page = $request->input('page', 1);

        $query = EmplacementsProduct::with([
            'creator',
            'updater',
        ]);

        if ($search = trim($request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('zone_stockage', 'like', "%{$search}%")
                    ->orWhere('equipement', 'like', "%{$search}%")
                    ->orWhere('position_detaillee', 'like', "%{$search}%");
            });
        }
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('is_primary')) {
            $query->where('is_primary', filter_var($request->is_primary, FILTER_VALIDATE_BOOLEAN));
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
     * @permission EmplacementsProductController::store
     * @permission_desc Créer les emplacements des produits
     */
    public function store(Request $request)
    {
        try {

            $auth = auth()->user();

            if (!$auth) {
                return response()->json([
                    'message' => 'Non authentifié'
                ], 401);
            }


            $data = $request->validate([
                'zone_stockage' => 'required|string|max:100',
                'equipement' => 'nullable|string|max:100',
                'position_detaillee' => 'nullable|string|max:100',
                'is_active' => 'sometimes|boolean',
                'is_primary' => 'sometimes|boolean',
            ]);

            if (!empty($data['is_primary']) && $data['is_primary'] == true) {
                $existsPrimary = EmplacementsProduct::where('is_primary', true)->exists();
                if ($existsPrimary) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Un emplacement principal existe déjà.',
                    ], 422);
                }
            }

            $emplacement = EmplacementsProduct::create([
                'zone_stockage' => strtoupper($data['zone_stockage']),
                'equipement' => $data['equipement'] ?? null,
                'position_detaillee' => $data['position_detaillee'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'is_primary'          => $data['is_primary'] ?? false,
                'created_by' => $auth->id,
                'updated_by' => $auth->id,
            ]);

            return response()->json([
                'message' => 'Emplacement créé avec succès',
                'data' => $emplacement
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Erreur serveur',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Display a listing of the resource.
     * @permission EmplacementsProductController::update
     * @permission_desc Modifier les emplacements des produits
     */
    public function update(Request $request, string $id)
    {
        try {

            $auth = auth()->user();

            if (!$auth) {
                return response()->json([
                    'message' => 'Non authentifié'
                ], 401);
            }

            $emplacement = EmplacementsProduct::find($id);

            if (!$emplacement) {
                return response()->json([
                    'message' => 'Emplacement introuvable'
                ], 404);
            }

            $data = $request->validate([
                'zone_stockage'       => 'required|string|max:100',
                'equipement'          => 'nullable|string|max:100',
                'position_detaillee'  => 'nullable|string|max:100',
                'is_active'           => 'sometimes|boolean',
                'is_primary'          => 'sometimes|boolean',
            ]);

            // 🔥 STRICT RULE : si on veut mettre primary
            if (!empty($data['is_primary']) && $data['is_primary'] == true) {

                $existsPrimary = EmplacementsProduct::where('is_primary', true)
                    ->where('id', '!=', $emplacement->id)
                    ->exists();

                if ($existsPrimary) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Un emplacement principal existe déjà.',
                    ], 422);
                }
            }

            // 🔥 update
            $emplacement->update([
                'zone_stockage'       => strtoupper($data['zone_stockage']),
                'equipement'          => $data['equipement'] ?? null,
                'position_detaillee'  => $data['position_detaillee'] ?? null,
                'is_active'           => $data['is_active'] ?? $emplacement->is_active,
                'is_primary'          => $data['is_primary'] ?? $emplacement->is_primary,

                'updated_by'          => $auth->id,
            ]);

            return response()->json([
                'message' => 'Emplacement mis à jour avec succès',
                'data' => $emplacement
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Erreur serveur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission EmplacementsProductController::update_status
     * @permission_desc Activer/Désactiver les emplacements des produits
     */
    public function update_status(Request $request, string $id)
    {
        $auth = auth()->user();
        $request->validate([
            'is_active' => 'required|boolean',
        ],[
            'is_active.required' => 'Le statut est obligatoire.',
        ]);
        $type = EmplacementsProduct::where('id', $id)->first();
        $type->is_active = $request->is_active;
        $type->updated_by = $auth->id;
        $type->save();
        return response()->json([
            'success' => true,
            "message" => "Statut modifié avec succès"
        ]);
    }
}
