<?php

namespace App\Http\Controllers;

use App\Models\Ordonnance;
use Illuminate\Http\Request;

class OrdonnanceController extends Controller
{
    public function index()
    {

    }

    /**
     * Display a listing of the resource.
     * @permission OrdonnanceController::store
     * @permission_desc Enregistrer des ordonnances pour des rapports de consultations
     */
    public function store(Request $request)
    {
        $auth = auth()->user();
       $request->validate([
            'rapport_consultations_id' => 'nullable|exists:ops_tbl_rapport_consultations,id',
            'description' => 'nullable|string',
        ]);

        $ordonnance = Ordonnance::create([
            'rapport_consultations_id' => $request->rapport_consultations_id,
            'description' => $request->description,
            'created_by' => $auth->id
        ]);

        return response()->json(['message' => 'Ordonnance enregistrée avec succès', 'data' => $ordonnance], 201);
    }
    /**
     * Display a listing of the resource.
     * @permission OrdonnanceController::update
     * @permission_desc Modifier des ordonnances pour des rapports de consultations
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();
        $ordonnance = Ordonnance::findOrFail($id);

        $validated = $request->validate([
            'rapport_consultations_id' => 'nullable|exists:ops_tbl_rapport_consultations,id',
            'description' => 'nullable|string',
        ]);

        $ordonnance->update([
            'rapport_consultations_id' => $validated['rapport_consultations_id'] ?? $ordonnance->rapport_consultations_id,
            'description' => $validated['description'] ?? $ordonnance->description,
            'updated_by' => $auth->id
        ]);

        return response()->json(['message' => 'Ordonnance mise à jour avec succès', 'data' => $ordonnance]);
    }
    //
}
