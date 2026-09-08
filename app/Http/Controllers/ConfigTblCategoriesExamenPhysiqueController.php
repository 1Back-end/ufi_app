<?php

namespace App\Http\Controllers;

use App\Models\ConfigTblCategoriesExamenPhysique;
use App\Models\ConfigTblSousCategorieAntecedent;
use App\Models\OpsTbl_Examen_Physique;
use Illuminate\Http\Request;

/**
 * @permission_category Gestion des catégories d'examen physique
 * @permission_module Paramètres Applicatifs
 */
class ConfigTblCategoriesExamenPhysiqueController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission ConfigTblCategoriesExamenPhysiqueController::index
     * @permission_desc Afficher la liste des catégories d'examen physique
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = ConfigTblCategoriesExamenPhysique::with(['creator:id,nom_utilisateur', 'updater:id,nom_utilisateur']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%")
                    ->orWhere('id', 'like', "%$search%");
            });
        }

        $results = $query->orderBy('order', 'asc')->latest()->paginate(perPage: $perPage, page: $page);

        return response()->json([
            'data' => $results->items(),
            'current_page' => $results->currentPage(),
            'last_page' => $results->lastPage(),
            'total' => $results->total(),
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission ConfigTblCategoriesExamenPhysiqueController::store
     * @permission_desc Création des catégories d'examen physique
     */

    public function store(Request $request)
    {
        $auth = auth()->user();

        $messages = [
            'name.required' => 'Le nom de la catégorie est obligatoire.',
            'name.unique' => 'Le nom existe deja.',
            'description.string' => 'La description doit être une chaîne de caractères.',
            'order.integer' => 'L\'ordre doit être un nombre entier.',
        ];

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:config_tbl_categories_examen_physiques,name',
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
        ], $messages);

        try {
            $validated['created_by'] = $auth->id;
            $validated['order'] = $request->input('order', 0);

            $categorie = ConfigTblCategoriesExamenPhysique::create($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Catégorie enregistrée avec succès.',
                'data' => $categorie
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'enregistrement',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Display a listing of the resource.
     * @permission ConfigTblCategoriesExamenPhysiqueController::update
     * @permission_desc Modification des catégories d'examen physique
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();

        $messages = [
            'name.required' => 'Le nom de la catégorie est obligatoire.',
            'name.unique' => 'Le nom existe deja.',
            'description.string' => 'La description doit être une chaîne de caractères.',
            'order.integer' => 'L\'ordre doit être un nombre entier.',
        ];

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:config_tbl_categories_examen_physiques,name,' . $id,
            'description' => 'nullable|string',
            'order' => 'nullable|integer',
        ], $messages);

        try {
            $categorie = ConfigTblCategoriesExamenPhysique::findOrFail($id);

            $validated['updated_by'] = $auth->id;

            // Conserve l'ordre existant si non fourni dans la requête
            if ($request->has('order')) {
                $validated['order'] = $request->input('order');
            }

            $categorie->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Catégorie modifiée avec succès.',
                'data' => $categorie
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la modification',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Display a listing of the resource.
     * @permission ConfigTblCategoriesExamenPhysiqueController::show
     * @permission_desc Afficher les détails des catégories d'examen physique
     */
    public function show($id)
    {
        $categorie = ConfigTblCategoriesExamenPhysique::where('is_deleted', false)->find($id);

        if (!$categorie) {
            return response()->json([
                'status' => 'error',
                'message' => 'Catégorie non trouvée.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Catégorie trouvée.',
            'data' => $categorie
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission ConfigTblCategoriesExamenPhysiqueController::destroy
     * @permission_desc Suppression des catégories d'examen physique
     */
    public function destroy($id)
    {
        $categorie = ConfigTblCategoriesExamenPhysique::find($id);

        if (!$categorie) {
            return response()->json([
                'status' => 'error',
                'message' => 'Catégorie non trouvée.'
            ], 404);
        }

        $isUsed = OpsTbl_Examen_Physique::where('categorie_examen_physique_id', $id)->exists();

        if ($isUsed) {
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de supprimer : cette catégorie est déjà utilisée.'
            ], 400);
        }

        $categorie->is_deleted = true;
        $categorie->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Catégorie supprimée avec succès.'
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission ConfigTblCategoriesExamenPhysiqueController::updateStatus
     * @permission_desc Activer/Désactiver des catégories d'examen physique
     */
    public function updateStatus($id)
    {
        $categorie = ConfigTblCategoriesExamenPhysique::find($id);

        if (!$categorie) {
            return response()->json([
                'status' => 'error',
                'message' => 'Catégorie non trouvée.'
            ], 404);
        }

        $categorie->is_active = !$categorie->is_active;
        $categorie->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Statut mis à jour avec succès.',
            'data' => $categorie
        ]);
    }




    //
}
