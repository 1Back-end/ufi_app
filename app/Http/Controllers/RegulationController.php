<?php

namespace App\Http\Controllers;

use App\Enums\StateFacture;
use App\Enums\StatusRegulation;
use App\Enums\TypeRegulation;
use App\Http\Requests\RegulationRequest;
use App\Models\Facture;
use App\Models\Regulation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Rules\ValidateAmountForRegulateFactureRule;
use Illuminate\Validation\Rules\Enum;

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
        foreach ($request->input('regulations') as $regulation) {
            $regulation = Regulation::create([
                'facture_id' => $request->input('facture_id'),
                'regulation_method_id' => $regulation['method'],
                'amount' => $regulation['amount'],
                'date' => now(),
                'type' => $request->input('type'),
                'comment' => $regulation['comment'] ?? null,
                'reason' => $regulation['reason'] ?? null,
            ]);

            $this->validatedFacture($regulation->facture);
        }

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
    public function update(Request $request, Regulation $regulation)
    {
        if ($regulation->state == StatusRegulation::CANCELLED->value) {
            return response()->json([
                'message' => 'La regulation est annulée'
            ], 400);
        }

        $request->validate([
            'regulation_method_id' => ['required', 'exists:regulation_methods,id'],
            'amount' => ['required', 'integer', new ValidateAmountForRegulateFactureRule($regulation->facture_id, $request->post('type'), $regulation->id)],
            'reason' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:255'],
            'type' => ['required', new Enum(TypeRegulation::class)],
        ]);

        $regulation->update([
            'regulation_method_id' => $request->post('regulation_method_id'),
            'amount' => $request->post('amount'),
            'reason' => $request->post('reason'),
            'comment' => $request->post('comment'),
            'type' => $request->post('type'),
        ]);

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
    public function cancel(Regulation $regulation, Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $regulation->update(['state' => StatusRegulation::CANCELLED, 'reason' => $request->input('reason')]);

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
        $amount = $facture->regulations()->where('regulations.state', '!=', StatusRegulation::CANCELLED->value)->sum('regulations.amount');

        $amountValidate = $amount == $facture->amount_client + $facture->amount_pc;
        $facture->update([
            'state' => $amountValidate ? StateFacture::PAID : StateFacture::IN_PROGRESS
        ]);

        if ($amountValidate) {
            $facture->prestation()->update([
                'regulated' => 2,
            ]);
        } else {
            $facture->prestation()->update([
                'regulated' => 1,
            ]);
        }
    }
}
