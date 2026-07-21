<?php

namespace App\Http\Controllers;

use App\Models\ProductType;
use Illuminate\Http\Request;
/**
 * @permission_category Gestion des types de produits
 * @permission_module Gestion des stocks
 * @permission_module Gestion des prestations
 */

class ProductTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission ProductTypeController::index
     * @permission_desc Afficher la liste des types de produits
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 5);
        $page = $request->input('page', 1);

        $query = ProductType::with(['creator', 'updater']);

        // Gestion de la recherche
        if ($search = trim($request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Pagination
        $productTypes = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'         => $productTypes->items(),
            'current_page' => $productTypes->currentPage(),
            'last_page'    => $productTypes->lastPage(),
            'total'        => $productTypes->total(),
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission ProductTypeController::store
     * @permission_desc Créer les types de produits
     */
    public function store(Request $request)
    {
        $auth = auth()->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:product_types,name',
            'accepts_galenic_form' => 'boolean',
            'accepts_generic_form' => 'boolean',
            'accepts_packaging' => 'boolean',
        ]);
        $validated['name'] = mb_strtoupper($validated['name'], 'UTF-8');
        $validated['created_by'] = $auth->id;

        $productType = ProductType::create($validated);

        return response()->json([
            'message' => 'Product type created successfully',
            'data' => $productType
        ], 201);
    }

    /**
     * Display a listing of the resource.
     * @permission ProductTypeController::show
     * @permission_desc Afficher les détails d'un type de produits
     */
    public function show(ProductType $productType)
    {
        return response()->json($productType->load(['creator', 'updater']));
    }

    /**
     * Display a listing of the resource.
     * @permission ProductTypeController::update
     * @permission_desc Modifier les type de produits
     */
    public function update(Request $request, ProductType $productType)
    {
        $auth = auth()->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:product_types,name,' . $productType->id,
            'accepts_galenic_form' => 'boolean',
            'accepts_generic_form' => 'boolean',
            'accepts_packaging' => 'boolean',
        ]);

        $validated['name'] = mb_strtoupper($validated['name'], 'UTF-8');
        $validated['updated_by'] = $auth->id;

        $productType->update($validated);

        return response()->json([
            'message' => 'Product type updated successfully',
            'data' => $productType
        ]);
    }


    /**
     * Display a listing of the resource.
     * @permission ProductTypeController::updateStatus
     * @permission_desc Activer/Désactiver les type de produits
     */
    public function updateStatus(Request $request, string $id)
    {
        $auth = auth()->user();

        $request->validate([
            'is_active' => 'required|boolean',
        ], [
            'is_active.required' => 'Le statut est obligatoire.',
        ]);

        $productType = ProductType::find($id);

        if (!$productType) {
            return response()->json([
                'success' => false,
                'message' => 'Type de produit introuvable.'
            ], 404);
        }

        $productType->is_active = $request->is_active;
        $productType->updated_by = $auth->id;
        $productType->save();

        return response()->json([
            'success' => true,
            'message' => 'Statut modifié avec succès.',
            'data' => $productType
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductType $productType)
    {
        //
    }
}
