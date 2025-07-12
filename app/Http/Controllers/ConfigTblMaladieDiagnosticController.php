<?php

namespace App\Http\Controllers;

use App\Models\ConfigTblMaladieDiagnostic;
use Illuminate\Http\Request;

class ConfigTblMaladieDiagnosticController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission ConfigTblMaladieDiagnosticController::index
     * @permission_desc Afficher la liste des maladies de diagnostics
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = ConfigTblMaladieDiagnostic::where('is_deleted', false)
            ->with(['creator', 'editor', 'sousCategorie']);

        if ($request->filled('sous_categorie_id')) {
            $query->where('sous_categorie_id', $request->input('sous_categorie_id'));
        }

        // Recherche globale (id, name, ou categorie.name)
        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhereHas('sousCategorie', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', "%{$search}%")
                            ->orWhere('id', 'like', "%{$search}%");
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
        //
    }

    /**
     * Display a listing of the resource.
     * @permission ConfigTblMaladieDiagnosticController::store
     * @permission_desc Création des maladies de diagnostics
     */
    public function store(Request $request)
    {
        $auth = auth()->user();
        $validated = $request->validate([
            'sous_categorie_id' => 'required|exists:config_sous_categorie_diagnostic,id',
            'name' => 'required|string|unique:config_tbl_maladie_diagnostic,name',
        ]);

        $validated['created_by'] = $auth->id;
        $maladie = ConfigTblMaladieDiagnostic::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Maladie enregistrée avec succès',
            'maladie' => $maladie,
        ]);
        //
    }

    /**
     * Display a listing of the resource.
     * @permission ConfigTblMaladieDiagnosticController::show
     * @permission_desc Afficher les détails des maladies de diagnostics
     */
    public function show(string $id)
    {

        $maladie = ConfigTblMaladieDiagnostic::findOrFail($id);

        return response()->json([
            'success' => true,
            'maladie' => $maladie,
        ]);
        //
    }

    /**
     * Display a listing of the resource.
     * @permission ConfigTblMaladieDiagnosticController::update
     * @permission_desc Mise à jour des maladies de diagnostics
     */
    public function update(Request $request, string $id)
    {
        $auth = auth()->user();

        $maladie = ConfigTblMaladieDiagnostic::findOrFail($id);

        $validated = $request->validate([
            'sous_categorie_id' => 'required|exists:config_sous_categorie_diagnostic,id',
            'name' => 'required|string|unique:config_tbl_maladie_diagnostic,name,' . $id,
        ]);

        $validated['updated_by'] = $auth->id;

        $maladie->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Maladie mise à jour avec succès',
            'maladie' => $maladie,
        ]);
        //
    }

    /**
     * Display a listing of the resource.
     * @permission ConfigTblMaladieDiagnosticController::destroy
     * @permission_desc Suppression des maladies de diagnostics
     */
    public function destroy(string $id)
    {
        $maladie = ConfigTblMaladieDiagnostic::findOrFail($id);

        $maladie->update(['is_deleted' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Maladie supprimée avec succès',
        ]);
    }
}
