<?php

namespace App\Http\Controllers;

use App\Models\ConfigTbl_Categories_enquetes;
use App\Models\ConfigTblCategoriesExamenPhysique;
use Illuminate\Http\Request;

class ConfigTblCategoriesEnquetesController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission ConfigTblCategoriesEnquetesController::index
     * @permission_desc Afficher la liste des catégories d'enquetes
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 5);  // Par défaut, 10 éléments par page
        $page = $request->input('page', 1);  // Page courante

        $diagnostic = ConfigTbl_Categories_enquetes::where('is_deleted', false)
            ->with(['creator:id,login','updater:id,login'])
            ->when($request->input('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%');
            })
            ->latest()->paginate(perPage: $perPage, page: $page);
        return response()->json([
            'data' => $diagnostic->items(),
            'current_page' => $diagnostic->currentPage(),  // Page courante
            'last_page' => $diagnostic->lastPage(),  // Dernière page
            'total' => $diagnostic->total(),  // Nombre total d'éléments
        ]);
        //
    }
    /**
     * Display a listing of the resource.
     * @permission ConfigTblCategoriesEnquetesController::store
     * @permission_desc Création des catégories d'enquetes
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        $messages = [
            'name.required' => 'Le nom de la catégorie est obligatoire.',
            'name.string' => 'Le nom de la catégorie doit être une chaîne de caractères.',
            'name.unique' => 'Cette catégorie existe déjà.',
            'description.string' => 'La description doit être une chaîne de caractères.',
        ];
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:configtbl_categories_enquetes,name',
            'description' => 'nullable|string',
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
     * @permission_desc Modification des catégories d'enquetes
     */
    public function update(Request $request, string $id)
    {
        $category = ConfigTbl_Categories_enquetes::findOrFail($id);

        $messages = [
            'name.required' => 'Le nom de la catégorie est obligatoire.',
            'name.string' => 'Le nom de la catégorie doit être une chaîne de caractères.',
            'name.unique' => 'Cette catégorie existe déjà.',
            'description.string' => 'La description doit être une chaîne de caractères.',
        ];

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:configtbl_categories_enquetes,name,' . $id,
            'description' => 'nullable|string',
        ], $messages);

        $category->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Catégorie mise à jour avec succès.',
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
     * @permission_desc Suppression des catégories d'enquetes
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

        $category->is_deleted = true;
        $category->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Catégorie supprimée avec succès'
        ]);
    }




    //
}
