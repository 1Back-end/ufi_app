<?php

namespace App\Http\Controllers;

use App\Models\ConfigTbl_Type_Diagnostic;
use Illuminate\Http\Request;

/**
 * @permission_category Gestion des Types de diagnostics
 */
class ConfigTblTypeDiagnosticController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission ConfigTblTypeDiagnosticController::index
     * @permission_desc Afficher la liste des Types de diagnostics
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 5);  // Par défaut, 10 éléments par page
        $page = $request->input('page', 1);  // Page courante

        $diagnostic = ConfigTbl_Type_Diagnostic::where('is_deleted', false)
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
     * @permission ConfigTblTypeDiagnosticController::store
     * @permission_desc Enregistrer les Types de diagnostics
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        $messages = [
            'name.required' => 'Le Type de diagnostic est obligatoire.',
            'name.unique' => 'Le Type de diagnostic existe déjà.',
            'description.string' => 'La description doit être une chaîne de caractères.',
        ];

        $validated = $request->validate([
            'name' => 'required|string|unique:configtbl_type_diagnostic,name',
            'description' => 'nullable|string',
            'has_nosologies' => 'nullable|boolean',
        ], $messages);

        $validated['created_by'] = $auth->id ?? null;

        $diagnostic = ConfigTbl_Type_Diagnostic::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Type de diagnostic enregistré avec succès.',
            'data' => $diagnostic
        ], 201);
    }


    /**
     * Display a listing of the resource.
     * @permission ConfigTblTypeDiagnosticController::show
     * @permission_desc Afficher les détails d'un  Types de diagnostic
     */
    public function show(string $id)
    {
        $diagnostic = ConfigTbl_Type_Diagnostic::find($id);
        if (!$diagnostic) {
            return response()->json([
                'status' => 'error',
                'message' => 'Type de diagnostic introuvable.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $diagnostic
        ]);
        //
    }


    /**
     * Display a listing of the resource.
     * @permission ConfigTblTypeDiagnosticController::update
     * @permission_desc Modifier des Types de diagnostics
     */
    public function update(Request $request, string $id)
    {
        $messages = [
            'name.required' => 'Le Type de diagnostic est obligatoire.',
            'name.unique' => 'Le Type de diagnostic existe déjà.',
            'description.string' => 'La description doit être une chaîne de caractères.',
        ];

        $validated = $request->validate([
            'name' => 'required|string|unique:configtbl_type_diagnostic,name,' . $id,
            'description' => 'nullable|string',
            'has_nosologies' => 'nullable|boolean',
        ], $messages);

        $diagnostic = ConfigTbl_Type_Diagnostic::findOrFail($id);
        $diagnostic->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Type de diagnostic mis à jour avec succès.',
            'data' => $diagnostic
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission ConfigTblTypeDiagnosticController::destroy
     * @permission_desc Supprimer les Types de diagnostics
     */
    public function destroy(string $id)
    {
        //
    }
}
