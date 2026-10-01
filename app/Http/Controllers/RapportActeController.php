<?php

namespace App\Http\Controllers;

use App\Enums\YesNoEnum;
use App\Models\OpsTblRapportConsultation;
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

        $rapportConsultation = \App\Models\OpsTblRapportConsultation::find($request->rapport_consultation_id);

        if (!$rapportConsultation) {
            return response()->json([
                'message' => "Rapport de consultation introuvable.",
                'success' => false,
            ], 404);
        }

        if (!$rapportConsultation->can_add_examens) {
            return response()->json([
                'message' => "Impossible d'ajouter des examens. L'autorisation (can_add_examens) n'est pas activée.",
                'success' => false,
            ], 422);
        }

        DB::transaction(function () use ($request, $auth, $rapportConsultation) {
            if ($request->type === YesNoEnum::OUI->value && $request->acte_id) {
                foreach ($request->acte_id as $acteId) {
                    RapportActe::create([
                        'rapport_consultation_id' => $request->rapport_consultation_id,
                        'acte_id' => $acteId,
                        'type' => YesNoEnum::OUI->value,
                        'created_by' => $auth->id,
                        'updated_by' => $auth->id,
                    ]);
                }
            }

            if ($request->type === YesNoEnum::NON->value && $request->actes_libres) {
                foreach ($request->actes_libres as $acteLibre) {
                    RapportActe::create([
                        'rapport_consultation_id' => $request->rapport_consultation_id,
                        'name' => $acteLibre['name'],
                        'description' => $acteLibre['description'] ?? null,
                        'type' => YesNoEnum::NON->value,
                        'created_by' => $auth->id,
                        'updated_by' => $auth->id,
                    ]);
                }
            }

            $rapportConsultation->update([
                'can_add_actes' => true,
                'updated_by' => $auth->id ?? null,
            ]);
        });

        return response()->json([
            'message' => 'Rapports actes enregistrés avec succès',
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
