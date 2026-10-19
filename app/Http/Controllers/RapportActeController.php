<?php

namespace App\Http\Controllers;

use App\Enums\TypePrestation;
use App\Enums\TypeVisitePrestation;
use App\Enums\YesNoEnum;
use App\Models\OpsTblRapportConsultation;
use App\Models\Prestation;
use App\Models\RapportActe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Enum;

/**
 * @permission_category Gestion du rapport des actes
 * @permission_module Gestion des prestations
 */

class RapportActeController extends Controller
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
     * @permission RapportActeController::store
     * @permission_desc Ajouter des actes au rapport de consultation d'un client
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        $request->validate([
            'rapport_consultation_id' => 'required|exists:ops_tbl_rapport_consultations,id',
            'type' => ['required', new Enum(YesNoEnum::class)],
            'acte_id' => 'nullable|array',
            'acte_id.*' => 'exists:actes,id',
            'actes_libres' => 'nullable|array',
            'actes_libres.*.name' => 'required_if:type,' . YesNoEnum::NON->value . '|string',
            'actes_libres.*.description' => 'nullable|string',
        ]);

        $rapportConsultation = OpsTblRapportConsultation::with('dossierConsultation.rendezVous')->find($request->rapport_consultation_id);

        if (!$rapportConsultation) {
            throw new \Exception("Rapport de consultation introuvable.");
        }

        $acteCenter = \App\Models\Centre::where('is_acte_recommendation_center', true)->first();
        $centreId = $acteCenter ? $acteCenter->id : $request->header('centre');

        if (!$centreId) {
            throw new \Exception("Aucun centre de recommandation ou d'en-tête valide n'a été trouvé.");
        }

        $clientId = null;
        $consultantId = null;

        if ($rapportConsultation->dossierConsultation) {
            $clientId = $rapportConsultation->dossierConsultation->client_id ?? optional($rapportConsultation->dossierConsultation->rendezVous)->client_id;

            $consultantId = $rapportConsultation->dossierConsultation->consultant_id
                ?? optional($rapportConsultation->dossierConsultation->rendezVous)->consultant_id
                ?? null;
        }

        DB::beginTransaction();
        try {
            if ($request->type === YesNoEnum::OUI->value && $request->filled('acte_id')) {
                $actesDataForPrestation = [];

                foreach ($request->acte_id as $acteId) {
                    $rapportActe = RapportActe::create([
                        'rapport_consultation_id' => $request->rapport_consultation_id,
                        'acte_id' => $acteId,
                        'type' => YesNoEnum::OUI->value,
                        'created_by' => $auth->id,
                        'updated_by' => $auth->id,
                    ]);

                    if (!$rapportActe) {
                        throw new \Exception("Erreur lors de l'enregistrement de l'acte interne.");
                    }

                    $acteModel = \App\Models\Acte::find($acteId);
                    if ($acteModel) {
                        $actesDataForPrestation[] = [
                            'id' => $acteModel->id,
                            'quantity' => 1,
                            'pu' => $acteModel->pu ?? 0,
                            'remise' => 0,
                            'b' => $acteModel->b ?? 0,
                        ];
                    }
                }

                if ($clientId && !empty($actesDataForPrestation)) {
                    $prestation = Prestation::create([
                        'type' => TypePrestation::ACTES->value ?? 'ACTE',
                        'type_visite' => TypeVisitePrestation::AUTRES->value,
                        'client_id' => $clientId,
                        'consultant_id' => $consultantId,
                        'centre_id' => $centreId,
                        'recommended_by_doctor' => true,
                        'programmation_date' => now(),
                        'created_by' => $auth->id,
                        'updated_by' => $auth->id,
                    ]);

                    if (!$prestation) {
                        throw new \Exception("Impossible de créer la prestation associée.");
                    }

                    foreach ($actesDataForPrestation as $act) {
                        $prestation->actes()->attach($act['id'], [
                            'quantity' => $act['quantity'],
                            'pu' => $act['pu'],
                            'remise' => $act['remise'],
                            'b' => $act['b'] ?? 0,
                        ]);
                    }
                } else {
                    throw new \Exception("Impossible de créer la prestation : client_id est vide ou aucun acte valide n'a été trouvé.");
                }
            }

            if ($request->type === YesNoEnum::NON->value && $request->filled('actes_libres')) {
                foreach ($request->actes_libres as $acteLibre) {
                    $rapportActeLibre = RapportActe::create([
                        'rapport_consultation_id' => $request->rapport_consultation_id,
                        'name' => $acteLibre['name'],
                        'description' => $acteLibre['description'] ?? null,
                        'type' => YesNoEnum::NON->value,
                        'created_by' => $auth->id,
                        'updated_by' => $auth->id,
                    ]);

                    if (!$rapportActeLibre) {
                        throw new \Exception("Erreur lors de l'enregistrement de l'acte libre.");
                    }
                }
            }

            $updated = $rapportConsultation->update([
                'can_add_actes' => true,
                'updated_by' => $auth->id ?? null,
            ]);

            if (!$updated) {
                throw new \Exception("Impossible de mettre à jour le rapport de consultation.");
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
            'message' => 'Rapports actes et prestation enregistrés avec succès',
            'success' => true,
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
