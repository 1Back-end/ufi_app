<?php

namespace App\Http\Controllers;

use App\Models\CategorieDiagnostic;
use App\Models\ConfigSousCategorieDiagnostic;
use App\Models\ConfigTblMaladieDiagnostic;
use Illuminate\Http\Request;

class CategorieDiagnosticController extends Controller
{

    /**
     * Display a listing of the resource.
     * @permission CategorieDiagnosticController::index
     * @permission_desc Afficher la liste des catégories de diagnostics
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = CategorieDiagnostic::where('is_deleted', false)
            ->with(['creator:id,login', 'editor:id,login']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
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
     * @permission CategorieDiagnosticController::store
     * @permission_desc Création des catégories de diagnostics
     */

    public function store(Request $request)
    {
        $auth = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|unique:categorie_diagnostic,name',
        ]);

        $validated['created_by'] = $auth->id;
        $categorie = CategorieDiagnostic::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Catégorie créée avec succès',
            'categorie' => $categorie,
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission CategorieDiagnosticController::update
     * @permission_desc Mise à jour des catégories de diagnostics
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();

        $categorie = CategorieDiagnostic::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|unique:categorie_diagnostic,name,' . $id,
        ]);

        $validated['updated_by'] = $auth->id;

        $categorie->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Catégorie mise à jour avec succès',
            'categorie' => $categorie,
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission CategorieDiagnosticController::show
     * @permission_desc Afficher les détails des catégories de diagnostics
     */
    public function show($id)
    {
        $categorie = CategorieDiagnostic::where('is_deleted', false)->findOrFail($id);

        return response()->json([
            'success' => true,
            'categorie' => $categorie,
        ]);
    }
    /**
     * Display a listing of the resource.
     * @permission CategorieDiagnosticController::destroy
     * @permission_desc Suppression des catégories de diagnostics
     */
    public function destroy($id)
    {
        $categorie = CategorieDiagnostic::where('is_deleted', false)->findOrFail($id);

        if($categorie->sousCategories()->exists()){
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer : des sous-catégories sont liées à cette catégorie.',
            ], 409); // 409 = Conflict
        }
        $categorie->update(['is_deleted' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Catégorie supprimée avec succès',
        ]);
    }



    public function getByCategorie($id)
    {
        try {
            $sousCategories = ConfigSousCategorieDiagnostic::where('categorie_id', $id)
                ->where('is_deleted', false)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $sousCategories,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getBySousCategorie($id)
    {
        try {
            $maladies = ConfigTblMaladieDiagnostic::where('sous_categorie_id', $id)
                ->where('is_deleted', false)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $maladies,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage(),
            ], 500);
        }
    }




    //
}
