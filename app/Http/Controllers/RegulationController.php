<?php

namespace App\Http\Controllers;

use App\Enums\StateFacture;
use App\Enums\StatusRegulation;
use App\Enums\TypePrestation;
use App\Enums\TypeRegulation;
use App\Http\Requests\RegulationRequest;
use App\Models\Acte;
use App\Models\Assureur;
use App\Models\Client;
use App\Models\Consultation;
use App\Models\FacturationAssurance;
use App\Models\Facture;
use App\Models\OpsTblHospitalisation;
use App\Models\Prestation;
use App\Models\Regulation;
use App\Models\RegulationMethod;
use App\Models\Soins;
use App\Models\SpecialRegulation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Rules\ValidateAmountForRegulateFactureRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Symfony\Component\HttpFoundation\Response;


/**
 * @permission_category Gestion des regulations
 */
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
                'phone' => $regulation['phone'] ?? null,
                'reference' => $regulation['reference'] ?? null,
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
            'phone' => [Rule::requiredIf(RegulationMethod::find($request->post('regulation_method_id'))->phone_method)],
            'reference' => [Rule::requiredIf(RegulationMethod::find($request->post('regulation_method_id'))->phone_method)],
        ]);

        $regulation->update([
            'regulation_method_id' => $request->post('regulation_method_id'),
            'amount' => $request->post('amount'),
            'reason' => $request->post('reason'),
            'comment' => $request->post('comment'),
            'type' => $request->post('type'),
            'phone' => $request->input('phone'),
            'reference' => $request->input('reference'),
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
     * @param Request $request
     * @return JsonResponse
     *
     * @throws \Throwable
     * @permission RegulationController::specialRegulation
     * @permission_desc Enregistrer une regulation spéciale
     */
    public function specialRegulation(Request $request)
    {
        $auth = auth()->user();
        $request->validate([
            'regulation_method_id' => ['required', 'exists:regulation_methods,id'],
            'amount' => ['required'],
            'assureur_id' => ['required_if:client_id,null', 'exists:assureurs,id'],
            'client_id' => ['required_if:assureur_id,null', 'exists:clients,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'number_piece' => ['required'],
            'comment' => ['required', 'string', 'max:255'],
            'date_piece' => ['required', 'date'],
            'factures' => ['required_if:allFacture,false', 'array'],
            'allFacture' => ['required', 'boolean'],
            'facture_ids' => ['array'],
            'facture_ids.*' => ['exists:factures,id'],
            'factures.*.id' => ['required', 'exists:factures,id'],
            'factures.*.items' => ['array'],
            'factures.*.amount' => ['required'],
            'type' => ['required', 'in:client,assureur'],
        ]);

        DB::beginTransaction();
        try {
            // Save Spécial regulate
            $regulateType = $request->type == 'client' ? Client::class : Assureur::class;
            $regulateId = $request->type == 'client' ? $request->input('client_id') : $request->input('assureur_id');

            $existing = FacturationAssurance::where('assurance_id', $regulateId)
                ->where(function ($q) use ($request) {
                    $start = \Carbon\Carbon::parse($request->start_date)->format('Y-m-d H:i:s');
                    $end = \Carbon\Carbon::parse($request->end_date)->format('Y-m-d H:i:s');

                    $q->whereBetween('start_date', [$start, $end])
                        ->orWhereBetween('end_date', [$start, $end])
                        ->orWhere(function($q2) use ($start, $end) {
                            $q2->where('start_date', '<=', $start)
                                ->where('end_date', '>=', $end);
                        });
                })
                ->exists();

            if ($existing) {
                DB::rollBack();
                return response()->json([
                    'status' => 'info',
                    'message' => 'Une facture pour cet assureur et cette période existe déjà. Aucune nouvelle facture créée.'
                ]);
            }

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

            FacturationAssurance::create([
                'start_date' => \Carbon\Carbon::parse($request->input('start_date'))->format('Y-m-d H:i:s'),
                'end_date' => \Carbon\Carbon::parse($request->input('end_date'))->format('Y-m-d H:i:s'),
                'assurance' => Assureur::class,
                'facture_number' => $request->input('number_piece'),
                'amount' => $request->input('amount'),
                'created_by' => $auth->id,
                'updated_by' => $auth->id,
                'assurance_id' => $regulateId,
            ]);

            // If all factures
            if ($request->input('allFacture')) {
                $prestations = Prestation::filterInProgress(
                    startDate: $request->input('start_date'),
                    endDate: $request->input('end_date'),
                    assurance: $request->input('assureur_id'),
                    payableBy: $request->input('client_id')
                )->get();

                foreach ($prestations as $prestation) {
                    $facture = $prestation->factures()->where('factures.type', 2)->where('factures.state', StateFacture::IN_PROGRESS->value)->first();

                    if (!in_array($facture->id, $request->input('facture_ids'))) {
                        $this->validatedFacture($facture, true);

                        $facture->regulations()->create([
                            'regulation_method_id' => $request->input('regulation_method_id'),
                            'amount' => $facture->amount_pc,
                            'date' => now(),
                            'type' => $request->type == 'client' ? 3 : 2,
                            'comment' => $request->input('comment'),
                            'particular' => true,
                        ]);

                        switch ($prestation->type) {
                            case TypePrestation::ACTES:
                                $prestation->actes->each(function (Acte $acte) use ($prestation) {
                                    $prestation->actes()->updateExistingPivot($acte->id, ['amount_regulate' => $acte->pivot->b * $acte->pivot->k_modulateur * 100]);
                                });
                                break;
                            case TypePrestation::CONSULTATIONS:
                                $prestation->consultations->each(function (Consultation $consultation) use ($prestation) {
                                    $prestation->consultations()->updateExistingPivot($consultation->id, ['amount_regulate' => $consultation->pivot->pu * 100]);
                                });
                                break;
                            case TypePrestation::SOINS:
                                $prestation->soins->each(function (Soins $soins) use ($prestation) {
                                    $prestation->soins()->updateExistingPivot($soins->id, ['amount_regulate' => $soins->pivot->pu * 100]);
                                });
                                break;
                            case TypePrestation::LABORATOIR:
                                $prestation->examens->each(function ($examen) use ($prestation) {
                                    $prestation->examens()->updateExistingPivot($examen->id, ['amount_regulate' => $examen->pivot->pu * 100]);
                                });
                                break;
                            case TypePrestation::PRODUITS:
                                $prestation->products->each(function ($product) use ($prestation) {
                                    $prestation->products()->updateExistingPivot($product->id, ['amount_regulate' => $product->pivot->pu * 100]);
                                });
                                break;
                            case TypePrestation::HOSPITALISATION:
                                $prestation->hospitalisations->each(function (OpsTblHospitalisation $hospitalisation) use ($prestation) {
                                    $prestation->hospitalisations()->updateExistingPivot($hospitalisation->id, ['amount_regulate' => $hospitalisation->pivot->pu * 100]);
                                });
                                break;
                            default:
                                throw new \Exception('To be implemented');
                        }
                    }
                }
            }

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
                    switch ($facture->prestation->type) {
                        case TypePrestation::ACTES:
                            $facture->prestation->actes()
                                ->updateExistingPivot($item['id'], ['amount_regulate' => $item['amount'] * 100]);
                            break;
                        case TypePrestation::CONSULTATIONS:
                            $facture->prestation->consultations()
                                ->updateExistingPivot($item['id'], ['amount_regulate' => $item['amount'] * 100]);
                            break;
                        case TypePrestation::SOINS:
                            $facture->prestation->soins()
                                ->updateExistingPivot($item['id'], ['amount_regulate' => $item['amount'] * 100]);
                            break;
                        case TypePrestation::LABORATOIR:
                            $facture->prestation->examens()
                                ->updateExistingPivot($item['id'], ['amount_regulate' => $item['amount'] * 100]);
                            break;
                        case TypePrestation::PRODUITS:
                            $facture->prestation->products()
                                ->updateExistingPivot($item['id'], ['amount_regulate' => $item['amount'] * 100]);
                            break;
                        case TypePrestation::HOSPITALISATION:
                            $facture->prestation->hospitalisations()
                                ->updateExistingPivot($item['id'], ['amount_regulate' => $item['amount'] * 100]);
                            break;
                        default:
                            // Ignorer ou logger le type non implémenté
                            \Log::warning("Type de prestation non implémenté: {$facture->prestation->type}");
                            break;
                    }

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
     * @param bool $update
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

    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @permission RegulationController::ignoreFacture
     * @permission_desc Ingorer les factures qui ne sont pas regler par l’assurance ou un client associer
     */
    public function ignoreFacture(Request $request)
    {
        $request->validate([
            'facture_ids' => ['required', 'array'],
            'facture_ids.*' => ['exists:factures,id']
        ]);

        foreach ($request->facture_ids as $facture_id) {
            $facture = Facture::find($facture_id);
            $this->validatedFacture($facture, true);

            $facture->update([
                'contentieux' => true,
            ]);
        }

        return \response()->json([
            'message' => __('Operation effectuee avec succes ')
        ], 202);
    }
}
