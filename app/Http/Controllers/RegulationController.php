<?php

namespace App\Http\Controllers;

use App\Enums\StateFacture;
use App\Enums\StatusRegulation;
use App\Http\Requests\RegulationRequest;
use App\Models\Facture;
use App\Models\Regulation;
use Illuminate\Http\JsonResponse;

class RegulationController extends Controller
{

    /**
     * @param RegulationRequest $request
     * @return JsonResponse
     *
     * @permission RegulationController::store
     * @permission_desc Enregistrer une regulation
     */
    public function store(RegulationRequest $request)
    {
        $regulation = Regulation::create($request->validated());

        $this->validatedFacture($regulation->facture);

        return response()->json([
            'message' => 'Enregistrement effectué avec succès'
        ], 201);
    }

    /**
     * @param RegulationRequest $request
     * @param Regulation $regulation
     * @return JsonResponse
     *
     * @permission RegulationController::update
     * @permission_desc Mettre à jour une regulation
     */
    public function update(RegulationRequest $request, Regulation $regulation)
    {
        if ($regulation->state == StatusRegulation::CANCELLED) {
            return response()->json([
                'message' => 'La regulation est annulée'
            ], 400);
        }

        $regulation->update($request->validated());

        $this->validatedFacture($regulation->facture);

        return response()->json([
            'message' => 'Mise à jour effectuée avec succès'
        ], 202);
    }

    /**
     * @param Regulation $regulation
     * @return JsonResponse
     *
     * @permission RegulationController::cancel
     * @permission_desc Annuler une regulation
     */
    public function cancel(Regulation $regulation)
    {
        $regulation->update(['state' => StatusRegulation::CANCELLED]);

        $this->validatedFacture($regulation->facture);

        return response()->json([], 202);
    }

    /**
     * Valide l'état d'une facture en fonction des réglements
     *
     * @param Facture $facture
     * @return void
     */
    protected function validatedFacture(Facture $facture)
    {
        $amount = $facture->regulations()->where('regulations.state', '!=', StatusRegulation::CANCELLED->value)->sum('amount');

        $facture->update([
           'state' => $amount == $facture->amount_client + $facture->amount_pc ? StateFacture::PAID : StateFacture::IN_PROGRESS
        ]);
    }
}
