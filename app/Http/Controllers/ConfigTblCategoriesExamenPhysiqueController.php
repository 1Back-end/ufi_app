<?php

namespace App\Http\Controllers;

use App\Models\ConfigTblCategoriesExamenPhysique;
use App\Models\ConfigTblSousCategorieAntecedent;
use Illuminate\Http\Request;

class ConfigTblCategoriesExamenPhysiqueController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission ConfigTblCategoriesExamenPhysiqueController::index
     * @permission_desc Afficher la liste des catégories d'examen physique
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 5);
        $page = $request->input('page', 1);

        $query = ConfigTblCategoriesExamenPhysique::where('is_deleted', false)
            ->with(['creator:id,login', 'updater:id,login']);

        // Ajout de la recherche si le champ 'search' est rempli
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%")
                    ->orWhere('id', 'like', "%$search%");
            });
        }

        $results = $query->latest()->paginate(perPage: $perPage, page: $page);

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
        ];

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:config_tbl_categories_examen_physiques,name',
            'description' => 'nullable|string',
        ], $messages);

        $validated['created_by'] = $auth->id;

        $categorie = ConfigTblCategoriesExamenPhysique::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Catégorie enregistrée avec succès.',
            'data' => $categorie
        ]);
    }
    /**
     * Display a listing of the resource.
     * @permission ConfigTblCategoriesExamenPhysiqueController::update
     * @permission_desc Modification des catégories d'examen physique
     */
    public function update(Request $request, $id)
    {
        $categorie = ConfigTblCategoriesExamenPhysique::findOrFail($id);

        $messages = [
            'name.required' => 'Le nom de la catégorie est obligatoire.',
            'name.unique' => 'Le nom existe deja.',
            'description.string' => 'La description doit être une chaîne de caractères.',
        ];

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:config_tbl_categories_examen_physiques,name'.$id,
            'description' => 'nullable|string',
        ], $messages);

        $validated['updated_by'] = auth()->id();

        $categorie->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Catégorie mise à jour avec succès.',
            'data' => $categorie
        ]);
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

        if (!$categorie || $categorie->is_deleted) {
            return response()->json([
                'status' => 'error',
                'message' => 'Catégorie introuvable ou déjà supprimée.'
            ], 404);
        }

        // Vérifie si la catégorie est utilisée ailleurs (ajuste si besoin)
        $isUsed = OpsTblExamenPhysique::where('categorie_examen_physique_id', $id)->exists();

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




    //
}
