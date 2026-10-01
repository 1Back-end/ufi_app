<?php

namespace App\Http\Controllers;

use App\Models\ConfigTbl_Type_Diagnostic;
use Illuminate\Http\Request;

/**
 * @permission_category Gestion des types de diagnostics
 * @permission_module Paramètres Applicatifs
 */
class ConfigTblTypeDiagnosticController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission ConfigTblTypeDiagnosticController::index
     * @permission_desc Afficher la liste des types de diagnostics
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $diagnostic = ConfigTbl_Type_Diagnostic::with(['creator', 'updater'])
            ->when($request->input('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%');
            })
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $diagnostic->items(),
            'current_page' => $diagnostic->currentPage(),
            'last_page' => $diagnostic->lastPage(),
            'total' => $diagnostic->total(),
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission ConfigTblTypeDiagnosticController::updateStatus
     * @permission_desc Activer/Désactiver les types de diagnostics
     */
    public function updateStatus($id)
    {
        $diagnostic = ConfigTbl_Type_Diagnostic::find($id);

        if (!$diagnostic) {
            return response()->json([
                'message' => "Type de diagnostic introuvable.",
                'success' => false,
            ], 404);
        }

        $diagnostic->is_active = !$diagnostic->is_active;
        $diagnostic->updated_by = auth()->id();
        $diagnostic->save();

        return response()->json([
            'message' => "Statut mis à jour avec succès.",
            'success' => true,
            'data' => $diagnostic
        ]);
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

        $validated['updated_by'] = auth()->id();

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
