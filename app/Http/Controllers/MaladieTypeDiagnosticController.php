<?php

namespace App\Http\Controllers;

use App\Models\MaladieTypeDiagnostic;
use App\Models\OpsTblRapportConsultation;
use Illuminate\Http\Request;

class MaladieTypeDiagnosticController extends Controller
{
    public function store(Request $request)
    {
        $auth = auth()->user();

        $request->validate([
            'disease_ids' => 'nullable|array',
            'disease_ids.*' => 'exists:diseases,id',
            'type_diagnostic_id' => 'required|exists:configtbl_type_diagnostic,id',
            'rapport_consultations_id' => 'required|exists:ops_tbl_rapport_consultations,id',
            'description' => 'nullable|string',
        ]);

        $consultation = OpsTblRapportConsultation::findOrFail($request->rapport_consultations_id);
        $consultation->update([
            'can_add_diagnostic' => true,
            'updated_by' => $auth->id
        ]);

        if (!empty($request->disease_ids)) {
            foreach ($request->disease_ids as $diseaseId) {
                MaladieTypeDiagnostic::create([
                    'maladie_id' => $diseaseId,
                    'rapport_consultations_id' => $request->rapport_consultations_id,
                    'type_diagnostic_id' => $request->type_diagnostic_id,
                    'description' => $request->description,
                    'created_by' => $auth->id,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Associations créées avec succès.'
        ], 201);
    }


    public function update(Request $request, $id)
    {
        $auth = auth()->user();

        $diagnostic = MaladieTypeDiagnostic::findOrFail($id);

        $request->validate([
            'disease_id' => 'required|exists:diseases,id',
            'type_diagnostic_id' => 'required|exists:configtbl_type_diagnostic,id',
            'rapport_consultations_id' => 'required|exists:ops_tbl_rapport_consultations,id',
            'description' => 'nullable|string',
        ]);

        $diagnostic->update([
            'disease_id' => $request->disease_id,
            'rapport_consultations_id' => $request->rapport_consultations_id,
            'type_diagnostic_id' => $request->type_diagnostic_id,
            'description' => $request->description,
            'updated_by' => $auth->id,
        ]);

        $consultation = OpsTblRapportConsultation::find($request->rapport_consultations_id);
        if ($consultation) {
            $consultation->update([
                'can_add_diagnostic' => true,
                'updated_by' => $auth->id
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Association mise à jour avec succès.',
            'data' => $diagnostic
        ], 200);
    }

    public function show($type_diagnostic_id)
    {
        $associations = MaladieTypeDiagnostic::with('maladie')
            ->where('type_diagnostic_id', $type_diagnostic_id)
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
