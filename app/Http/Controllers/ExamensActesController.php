<?php

namespace App\Http\Controllers;

use App\Enums\YesNoEnum;
use App\Models\ExamenActes;
use App\Models\OpsTblRapportConsultation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;


/**
 * @permission_category Gestion des examens au rapport de consultation d'un client
 * @permission_module Gestion des prestations
 */
class ExamensActesController extends Controller
{
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
            'type' => ['required', new Enum(YesNoEnum::class)],
            'examen_id' => 'nullable|array',
            'examen_id.*' => 'exists:examens,id',
            'examens_libres' => 'nullable|array',
            'examens_libres.*.name' => 'required_if:type,' . YesNoEnum::NON->value . '|string',
            'examens_libres.*.description' => 'nullable|string',
        ]);

        if ($request->type === YesNoEnum::OUI->value && $request->filled('examen_id')) {
            foreach ($request->examen_id as $examenId) {
                ExamenActes::create([
                    'rapport_consultation_id' => $request->rapport_consultation_id,
                    'examen_id' => $examenId,
                    'type' => YesNoEnum::OUI->value,
                    'created_by' => $auth->id,
                    'updated_by' => $auth->id,
                ]);
            }
        }

        if ($request->type === YesNoEnum::NON->value && $request->filled('examens_libres')) {
            foreach ($request->examens_libres as $examenLibre) {
                ExamenActes::create([
                    'rapport_consultation_id' => $request->rapport_consultation_id,
                    'name' => $examenLibre['name'],
                    'description' => $examenLibre['description'] ?? null,
                    'type' => YesNoEnum::NON->value,
                    'created_by' => $auth->id,
                    'updated_by' => $auth->id,
                ]);
            }
        }

        if ($request->rapport_consultation_id) {
            $rapport = OpsTblRapportConsultation::find($request->rapport_consultation_id);

            if ($rapport) {
                $rapport->can_add_examens = true;
                $rapport->updated_by = $auth->id ?? null;
                $rapport->save();

                if ($rapport->dossierConsultation && $rapport->dossierConsultation->rendezVous) {
                    $rendezVous = $rapport->dossierConsultation->rendezVous;
                    $rendezVous->etat = 'Clos';
                    $rendezVous->updated_by = $auth->id ?? null;
                    $rendezVous->save();
                }
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
