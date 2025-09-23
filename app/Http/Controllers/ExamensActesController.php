<?php

namespace App\Http\Controllers;

use App\Models\ExamenActes;
use App\Models\OpsTblRapportConsultation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


/**
 * @permission_category Gestion des examens au rapport de consultation d'un client
 */
class ExamensActesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * @return JsonResponse
     *
     * @permission ExamensActesController::store
     * @permission_desc Ajouter des examens au rapport de consultation d'un client
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        $request->validate([
            'rapport_consultation_id' => 'nullable|exists:ops_tbl_rapport_consultations,id',
            'type' => 'required|string|in:Oui,Non',
            'examen_id' => 'nullable|array',
            'examen_id.*' => 'exists:examens,id',
            'examens_libres' => 'nullable|array',
            'examens_libres.*.name' => 'required_if:type,Non|string',
            'examens_libres.*.description' => 'nullable|string',
        ]);

        // Cas "Oui" : plusieurs examens sélectionnés
        if ($request->type === 'Oui' && $request->examen_id) {
            foreach ($request->examen_id as $examenId) {
                ExamenActes::create([
                    'rapport_consultation_id' => $request->rapport_consultation_id,
                    'examen_id' => $examenId,
                    'type' => 'Oui',
                    'created_by' => $auth->id,
                    'updated_by' => $auth->id,
                ]);
            }
        }

        // Cas "Non" : examens libres
        if ($request->type === 'Non' && $request->examens_libres) {
            foreach ($request->examens_libres as $examenLibre) {
                ExamenActes::create([
                    'rapport_consultation_id' => $request->rapport_consultation_id,
                    'name' => $examenLibre['name'],
                    'description' => $examenLibre['description'] ?? null,
                    'type' => 'Non',
                    'created_by' => $auth->id,
                    'updated_by' => $auth->id,
                ]);
            }
        }

        if ($request->rapport_consultation_id) {
            $rapport = OpsTblRapportConsultation::find($request->rapport_consultation_id);

            if ($rapport && $rapport->dossierConsultation && $rapport->dossierConsultation->rendezVous) {
                $rendezVous = $rapport->dossierConsultation->rendezVous;
                $rendezVous->etat = 'Clos';
                $rendezVous->updated_by = $auth->id ?? null;
                $rendezVous->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Enregistrement effectué avec succès',
        ]);
    }



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
