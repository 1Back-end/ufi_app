<?php

namespace App\Http\Controllers;

use App\Models\ConfigTbl_Categories_enquetes;
use App\Models\ConfigTblCategoriesExamenPhysique;
use App\Models\OpsTblEnquete;
use Illuminate\Http\Request;

/**
 * @permission_category Gestion des catégories d'enquetes
 * @permission_module Paramètres Applicatifs
 */

class ConfigTblCategoriesEnquetesController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission ConfigTblCategoriesEnquetesController::index
     * @permission_desc Afficher la liste des catégories d'enquetes
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = ConfigTbl_Categories_enquetes::with(['creator:id,nom_utilisateur', 'updater:id,nom_utilisateur']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%")
                    ->orWhere('id', 'like', "%$search%");
            });
        }

        $results = $query->orderBy('order', 'asc')->paginate(perPage: $perPage, page: $page);

        return response()->json([
            'data' => $results->items(),
            'current_page' => $results->currentPage(),
            'last_page' => $results->lastPage(),
            'total' => $results->total(),
        ]);
    }
    /**
     * Display a listing of the resource.
     * @permission ConfigTblCategoriesEnquetesController::store
     * @permission_desc Enregistrer des catégories d'enquetes
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        $messages = [
            'name.required' => 'Le nom de la catégorie est obligatoire.',
            'name.string' => 'Le nom de la catégorie doit être une chaîne de caractères.',
            'name.unique' => 'Cette catégorie existe déjà.',
            'description.string' => 'La description doit être une chaîne de caractères.',
            'order.integer' => "L'ordre doit être un nombre entier.",
        ];
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:configtbl_categories_enquetes,name',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
        ], $messages);

        $validated['created_by'] = $auth->id ?? null;
        $category = ConfigTbl_Categories_enquetes::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Catégorie enregistrée avec succès.',
            'data' => $category
        ], 201);
    }

    /**
     * Display a listing of the resource.
     * @permission ConfigTblCategoriesEnquetesController::update
     * @permission_desc Modifier des catégories d'enquetes
     */
    public function update(Request $request, $id)
    {
        $category = ConfigTbl_Categories_enquetes::find($id);

        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Catégorie non trouvée.'
            ], 404);
        }

        $auth = auth()->user();

        $messages = [
            'name.required' => 'Le nom de la catégorie est obligatoire.',
            'name.string' => 'Le nom de la catégorie doit être une chaîne de caractères.',
            'name.unique' => 'Cette catégorie existe déjà.',
            'description.string' => 'La description doit être une chaîne de caractères.',
            'order.integer' => "L'ordre doit être un nombre entier.",
            'is_active.boolean' => 'Le statut actif doit être un booléen.',
        ];

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:configtbl_categories_enquetes,name,' . $id,
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ], $messages);

        $validated['updated_by'] = $auth->id ?? null;
        $category->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Catégorie modifiée avec succès.',
            'data' => $category
        ]);
    }
    /**
     * Display a listing of the resource.
     * @permission ConfigTblCategoriesEnquetesController::show
     * @permission_desc Afficher les détails des catégories d'enquetes
     */

    public function show(string $id)
    {
        $category = ConfigTbl_Categories_enquetes::find($id);

        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Catégorie non trouvée.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $category
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission ConfigTblCategoriesEnquetesController::destroy
     * @permission_desc Supprimer des catégories d'enquetes
     */
    public function destroy(string $id)
    {
        $category = ConfigTbl_Categories_enquetes::find($id);

        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Catégorie non trouvée.'
            ], 404);
        }

        $isUsed = OpsTblEnquete::where('categories_enquetes_id', $id)->exists();

        if ($isUsed) {
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de supprimer : cette catégorie est déjà utilisée.'
            ], 400);
        }

        $category->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Catégorie supprimée avec succès'
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission ConfigTblCategoriesEnquetesController::updateStatus
     * @permission_desc Activer/Désactiver des catégories d'enquetes
     */
    public function updateStatus($id)
    {
        $category = ConfigTbl_Categories_enquetes::find($id);

        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Catégorie non trouvée.'
            ], 404);
        }

        $category->is_active = !$category->is_active;
        $category->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Statut mis à jour avec succès.',
            'data' => $category
        ]);
    }




    //
}
