<?php

namespace App\Http\Controllers;

use App\Enums\StateFacture;
use App\Enums\StatusRegulation;
use App\Enums\TypePrestation;
use App\Enums\TypeRegulation;
use App\Http\Requests\RegulationRequest;
use App\Models\Assureur;
use App\Models\Client;
use App\Models\Facture;
use App\Models\Regulation;
use App\Models\SpecialRegulation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Rules\ValidateAmountForRegulateFactureRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Enum;
use Symfony\Component\HttpFoundation\Response;

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
        $facture = Facture::find($request->input('facture_id'));
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
        }

        $this->validatedFacture($facture);

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

        $this->validatedFacture($regulation->facture, false, true);

        return response()->json([
            'message' => 'Mise à jour effectuée avec succès'
        ], 202);
    }

    /**
     * @param Regulation $regulation
     * @param Request $request
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

        $this->validatedFacture($regulation->facture, false, true);

        return response()->json([], 202);
    }

    /**
     * @param Regulation $regulation
     * @return JsonResponse
     *
     * @permission RegulationController::specialRegulation
     * @permission_desc Enregistrer une regulation spéciale
     */
    public function specialRegulation(Request $request)
    {
        $request->validate([
            'regulation_method_id' => ['required', 'exists:regulation_methods,id'],
            'amount' => ['required', 'integer'],
            'assureur_id' => ['required_if:client_id,null', 'exists:assureurs,id'],
            'client_id' => ['required_if:assureur_id,null', 'exists:clients,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'number_piece' => ['required'],
            'comment' => ['required', 'string', 'max:255'],
            'date_piece' => ['required', 'date'],
            'factures' => ['required', 'array'],
            'factures.*.id' => ['required', 'exists:factures,id'],
            'factures.*.items' => ['array'],
            'factures.*.amount' => ['required',],
            'type' => ['required', 'in:client,assureur'],
        ]);

        DB::beginTransaction();
        try {
            // Save Spécial regulate
            $regulateType = $request->type == 'client' ? Client::class : Assureur::class;
            $regulateId = $request->type == 'client' ? $request->input('client_id') : $request->input('assureur_id');

            SpecialRegulation::create([
                'regulation_id' => $regulateId,
                'regulation_type' => $regulateType,
                'regulation_method_id' => $request->input('regulation_method_id'),
                'amount' => $request->input('amount'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'number_piece' => $request->input('number_piece'),
                'date_piece' => $request->input('date_piece'),
            ]);

            foreach ($request->input('factures') as $factureData) {
                $facture = Facture::find($factureData['id']);
                $this->validatedFacture($facture, true);

                $facture->regulations()->create([
                    'regulation_method_id' => $request->input('regulation_method_id'),
                    'amount' => $factureData['amount'],
                    'date' => now(),
                    'type' => $request->type == 'client' ? 3 : 2,
                    'comment' => $request->input('comment'),
                    'particular' => true,
                ]);

                if ($request->type == 'client' && $factureData['amount'] < $facture->amount_client) {
                    $facture->update([
                        'contentieux' => true,
                    ]);
                }

                if ($request->type == 'assureur' && $factureData['amount'] < $facture->amount_pc) {
                    $facture->update([
                        'contentieux' => true,
                    ]);
                }

                foreach ($factureData['items'] as $item) {
                    $facture->prestation->actes()
                        ->updateExistingPivot($item['id'], ['amount_regulate' => $item['amount']]);
                }
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], $e->getCode() === 0 ? Response::HTTP_INTERNAL_SERVER_ERROR : Response::HTTP_BAD_REQUEST);
        }
        DB::commit();

        return response()->json([
            'message' => 'Enregistrement effectué avec succès'
        ], 201);
    }

    /**
     * Valide l’état d’une facture en fonction des règlements
     *
     * @param Facture $facture
     * @param bool $forcePaid
     * @return void
     */
    protected function validatedFacture(Facture $facture, bool $forcePaid = false, bool $update = false)
    {
        if ($facture->state == StateFacture::PAID && !$update) {
            return;
        }

        if ($forcePaid) {
            $facture->update([
                'state' => StateFacture::PAID,
            ]);

            $facture->prestation()->update([
                'regulated' => 2,
            ]);
            return;
        }

        $amount = $facture->regulations()
            ->where('regulations.state', '!=', StatusRegulation::CANCELLED->value)
            ->sum('regulations.amount');

        $amountValidate = ($amount / 100) == $facture->amount_client + $facture->amount_pc;
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
