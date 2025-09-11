<?php

namespace App\Http\Controllers;

use App\Models\ConfigSousCategorieDiagnostic;
use Illuminate\Http\Request;


/**
 * @permission_category Gestion des catégories de diagnostics
 */
class ConfigSousCategorieDiagnosticController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission ConfigSousCategorieDiagnosticController::index
     * @permission_desc Afficher la liste des sous catégories de diagnostics
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = ConfigSousCategorieDiagnostic::where('is_deleted', false)
            ->with(['creator', 'editor', 'categorie']);

        // Filtrage par catégorie_id direct
        if ($request->filled('categorie_id')) {
            $query->where('categorie_id', $request->input('categorie_id'));
        }

        // Recherche globale (id, name, ou categorie.name)
        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhereHas('categorie', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', "%{$search}%");
                    });
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
     * @permission ConfigSousCategorieDiagnosticController::store
     * @permission_desc Création des sous catégories de diagnostics
     */
    public function store(Request $request)
    {
        $auth = auth()->user();
        $validated = $request->validate([
            'categorie_id' => 'required|exists:categorie_diagnostic,id',
            'name' => 'required|string|unique:config_sous_categorie_diagnostic,name',
        ]);

        $validated['created_by'] = $auth->id;
        $sousCategorie = ConfigSousCategorieDiagnostic::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Sous-catégorie créée avec succès',
            'sousCategorie' => $sousCategorie,
        ]);

    }

    /**
     * Display a listing of the resource.
     * @permission ConfigSousCategorieDiagnosticController::show
     * @permission_desc Afficher les détails des sous catégories de diagnostics
     */
    public function show($id)
    {
        $sousCategorie = ConfigSousCategorieDiagnostic::where('is_deleted', false)->findOrFail($id);

        return response()->json([
            'success' => true,
            'sousCategorie' => $sousCategorie,
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission ConfigSousCategorieDiagnosticController::update
     * @permission_desc Modification des sous catégories de diagnostics
     */
    public function update(Request $request, string $id)
    {
        $auth = auth()->user();

        $sousCategorie = ConfigSousCategorieDiagnostic::findOrFail($id);

        $validated = $request->validate([
            'categorie_id' => 'required|exists:categorie_diagnostic,id',
            'name' => 'required|string|unique:config_sous_categorie_diagnostic,name,' . $id,
        ]);

        $validated['updated_by'] = $auth->id;

        $sousCategorie->update($validated);
        return response()->json([
            'success' => true,
            'message' => 'Sous-catégorie mise à jour avec succès',
            'sousCategorie' => $sousCategorie,
        ]);
        //
    }

    /**
     * Display a listing of the resource.
     * @permission ConfigSousCategorieDiagnosticController::destroy
     * @permission_desc Suppression des sous catégories de diagnostics
     */
    public function destroy($id)
    {
        $sousCategorie = ConfigSousCategorieDiagnostic::where('is_deleted', false)->findOrFail($id);

        if ($sousCategorie->maladies()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer : des maladies sont liées à cette sous-catégorie.',
            ], 409); // 409 = Conflict
        }

        $sousCategorie->update(['is_deleted' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Sous-catégorie supprimée avec succès.',
        ]);
    }
}
