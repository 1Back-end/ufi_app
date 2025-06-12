<?php

namespace App\Http\Controllers;

use App\Models\Diagnostic;
use Illuminate\Http\Request;

class DiagnosticController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission DiagnosticController::store
     * @permission_desc Création des diagnostics des dossiers de consultations
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        $request->validate([
            'rapport_consultations_id' => 'nullable|exists:ops_tbl_rapport_consultations,id',
            'type_diagnostic_id' => 'nullable|exists:configtbl_type_diagnostic,id',
            'description' => 'nullable|string',

        ]);

        $diagnostic = Diagnostic::create([
            'rapport_consultations_id' => $request->rapport_consultations_id,
            'type_diagnostic_id' => $request->type_diagnostic_id,
            'description'  => $request->description,
            'created_by' => $auth->id,
        ]);

        return response()->json([
            'message' => 'Diagnostic enregistré avec succès.',
            'data' => $diagnostic
        ], 201);
    }

    /**
     * Display a listing of the resource.
     * @permission DiagnosticController::update
     * @permission_desc Modification des diagnostics des dossiers de consultations
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();

        $request->validate([
            'rapport_consultations_id' => 'nullable|exists:ops_tbl_rapport_consultations,id',
            'type_diagnostic_id' => 'nullable|exists:configtbl_type_diagnostic,id',
            'description' => 'nullable|string',

        ]);

        $diagnostic = Diagnostic::findOrFail($id);

        $diagnostic->update([
            'code' => $request->code,
            'rapport_consultations_id' => $request->rapport_consultations_id,
            'type_diagnostic_id' => $request->type_diagnostic_id,
            'description'  => $request->description,
            'updated_by' => $auth->id,
        ]);

        return response()->json([
            'message' => 'Diagnostic mis à jour avec succès.',
            'data' => $diagnostic
        ], 200);
    }

    //
}
