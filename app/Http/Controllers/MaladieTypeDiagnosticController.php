<?php

namespace App\Http\Controllers;

use App\Models\MaladieTypeDiagnostic;
use Illuminate\Http\Request;

class MaladieTypeDiagnosticController extends Controller
{
    public function store(Request $request)
    {
        $auth = auth()->user();

        $request->validate([
            'maladie_ids' => 'required|array|min:1',
            'maladie_ids.*' => 'exists:maladies,id',
            'type_diagnostic_id' => 'required|exists:configtbl_type_diagnostic,id',
            'rapport_consultations_id' => 'required|exists:ops_tbl_rapport_consultations,id',
            'description' => 'nullable|string',
        ]);

        foreach ($request->maladie_ids as $maladieId) {
            MaladieTypeDiagnostic::create([
                'maladie_id' => $maladieId,
                'rapport_consultations_id' => $request->rapport_consultations_id,
                'type_diagnostic_id' => $request->type_diagnostic_id,
                'description' => $request->description,
                'created_by' => $auth->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Associations créées avec succès.'
        ], 201);
    }



    public function update(Request $request, $type_diagnostic_id)
    {
        $auth = auth()->user();

        $request->validate([
            'maladie_id' => 'required|array|min:1',
            'maladie_id.*' => 'exists:maladies,id',
            'type_diagnostic_id' => 'required|exists:configtbl_type_diagnostic,id',
            'rapport_consultations_id' => 'required|exists:ops_tbl_rapport_consultations,id',
            'description' => 'nullable|string',
        ]);

        // Supprimer les anciennes associations (soft delete ou hard delete)
        MaladieTypeDiagnostic::where('type_diagnostic_id', $type_diagnostic_id)->delete();

        // Recréer les nouvelles
        foreach ($request->maladie_id as $maladieId) {
            MaladieTypeDiagnostic::create([
                'maladie_id' => $maladieId,
                'type_diagnostic_id' => $type_diagnostic_id,
                'rapport_consultations_id' => $request->rapport_consultations_id,
                'description' => $request->description,
                'created_by' => $auth->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Associations mises à jour avec succès.'
        ]);
    }

    public function show($type_diagnostic_id)
    {
        $associations = MaladieTypeDiagnostic::with('maladie')
            ->where('type_diagnostic_id', $type_diagnostic_id)
            ->where('is_deleted', false)  // si tu utilises suppression logique
            ->get();

        return response()->json([
            'success' => true,
            'data' => $associations
        ]);
    }

    public function destroy($id)
    {
        $auth = auth()->user();

        $association = MaladieTypeDiagnostic::findOrFail($id);

        $association->is_deleted = true;
        $association->updated_by = $auth->id;
        $association->save();

        return response()->json([
            'success' => true,
            'message' => 'Association supprimée avec succès.'
        ]);
    }




    //
}
