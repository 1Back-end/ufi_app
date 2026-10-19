<?php

namespace App\Http\Controllers;

use App\Enums\TypePrestation;
use App\Enums\TypeVisitePrestation;
use App\Enums\YesNoEnum;
use App\Models\ExamenActes;
use App\Models\OpsTblRapportConsultation;
use App\Models\Prestation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $rapport = null;
        $clientId = null;
        $consultantId = null;

        $examCenter = \App\Models\Centre::where('is_exam_recommendation_center', true)->first();
        $centreId = $examCenter ? $examCenter->id : $request->header('centre');

        if (!$centreId) {
            throw new \Exception("Aucun centre de recommandation ou d'en-tête valide n'a été trouvé.");
        }

        if ($request->rapport_consultation_id) {
            $rapport = OpsTblRapportConsultation::with('dossierConsultation.rendezVous')->find($request->rapport_consultation_id);
            if ($rapport && $rapport->dossierConsultation) {
                $clientId = $rapport->dossierConsultation->client_id ?? optional($rapport->dossierConsultation->rendezVous)->client_id;

                $consultantId = $rapport->dossierConsultation->consultant_id
                    ?? optional($rapport->dossierConsultation->rendezVous)->consultant_id
                    ?? null;
            }
        }

        DB::beginTransaction();
        try {
            if ($request->type === YesNoEnum::OUI->value && $request->filled('examen_id')) {
                $examensDataForPrestation = [];
                $maxDurationInDays = 0; // Stocke la durée maximale en jours

                foreach ($request->examen_id as $examenId) {
                    $examenActes = ExamenActes::create([
                        'rapport_consultation_id' => $request->rapport_consultation_id,
                        'examen_id' => $examenId,
                        'type' => YesNoEnum::OUI->value,
                        'created_by' => $auth->id,
                        'updated_by' => $auth->id,
                    ]);

                    if (!$examenActes) {
                        throw new \Exception("Erreur lors de l'enregistrement de l'examen interne.");
                    }

                    $examenModel = \App\Models\Examen::find($examenId);
                    if ($examenModel) {
                        $examensDataForPrestation[] = [
                            'id' => $examenModel->id,
                            'quantity' => 1,
                            'pu' => $examenModel->price ?? 0,
                            'remise' => 0,
                            'b' => $examenModel->b ?? 0,
                        ];

                        // Récupération de la durée en jours et recherche du maximum
                        $duration = $examenModel->renderer_duration ?? 0;
                        if ($duration > $maxDurationInDays) {
                            $maxDurationInDays = $duration;
                        }
                    }
                }

                if ($clientId && !empty($examensDataForPrestation)) {
                    // Calcul de la date de remise en ajoutant les jours du délai le plus long
                    $resultDeliveredAt = $maxDurationInDays > 0
                        ? now()->addDays($maxDurationInDays)
                        : now();

                    $prestation = Prestation::create([
                        'type' => TypePrestation::LABORATOIR->value,
                        'type_visite' => TypeVisitePrestation::AUTRES->value,
                        'client_id' => $clientId,
                        'consultant_id' => $consultantId,
                        'centre_id' => $centreId,
                        'recommended_by_doctor' => true,
                        'programmation_date' => now(),
                        'result_delivered_at' => $resultDeliveredAt,
                        'created_by' => $auth->id,
                        'updated_by' => $auth->id,
                    ]);

                    if (!$prestation) {
                        throw new \Exception("Impossible de créer la prestation associée.");
                    }

                    foreach ($examensDataForPrestation as $ex) {
                        $prestation->examens()->attach($ex['id'], [
                            'quantity' => $ex['quantity'],
                            'pu' => $ex['pu'],
                            'remise' => $ex['remise'],
                            'b' => $ex['b'] ?? 0,
                        ]);
                    }
                } else {
                    throw new \Exception("Impossible de créer la prestation : client_id est vide ou aucun examen valide n'a été trouvé.");
                }
            }

            if ($request->type === YesNoEnum::NON->value && $request->filled('examens_libres')) {
                foreach ($request->examens_libres as $examenLibre) {
                    $examenLibreCreated = ExamenActes::create([
                        'rapport_consultation_id' => $request->rapport_consultation_id,
                        'name' => $examenLibre['name'],
                        'description' => $examenLibre['description'] ?? null,
                        'type' => YesNoEnum::NON->value,
                        'created_by' => $auth->id,
                        'updated_by' => $auth->id,
                    ]);

                    if (!$examenLibreCreated) {
                        throw new \Exception("Erreur lors de l'enregistrement de l'examen libre.");
                    }
                }
            }

            if ($rapport) {
                $rapport->can_add_examens = true;
                $rapport->updated_by = $auth->id ?? null;
                $saved = $rapport->save();

                if (!$saved) {
                    throw new \Exception("Impossible de mettre à jour le rapport de consultation.");
                }

                if ($rapport->dossierConsultation && $rapport->dossierConsultation->rendezVous) {
                    $rendezVous = $rapport->dossierConsultation->rendezVous;
                    $rendezVous->etat = 'Clos';
                    $rendezVous->updated_by = $auth->id ?? null;
                    $rdvSaved = $rendezVous->save();

                    if (!$rdvSaved) {
                        throw new \Exception("Impossible de clore le rendez-vous associé.");
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
                'success' => false,
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Enregistrement et création de la prestation effectués avec succès',
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
