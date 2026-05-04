<?php

namespace App\Http\Controllers;

use App\Enums\StateFacture;
use App\Enums\StatusRegulation;
use App\Enums\TypePrestation;
use App\Enums\TypeRegulation;
use App\Http\Requests\RegulationRequest;
use App\Models\Acte;
use App\Models\Assureur;
use App\Models\Caisse;
use App\Models\Centre;
use App\Models\Client;
use App\Models\Consultation;
use App\Models\FacturationAssurance;
use App\Models\Facture;
use App\Models\OpsTblHospitalisation;
use App\Models\Prestation;
use App\Models\Regulation;
use App\Models\RegulationMethod;
use App\Models\SessionCaisse;
use App\Models\SessionElement;
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
        $auth = auth()->user();
        $centreId = $request->header('centre');
        $facture = Facture::find($request->input('facture_id'));

        // 🔹 Récupérer la caisse active pour cet utilisateur et ce centre
        $caisse = Caisse::where('user_id', $auth->id)
            ->where('centre_id', $centreId)
            ->where('is_active', true)
            ->first();

        if (!$caisse) {
            return response()->json([
                'message' => "Aucune caisse active assignée à cet utilisateur pour ce centre."
            ], 403);
        }

        // 🔹 Chercher la session ouverte pour cette caisse et ce centre
        $session = SessionCaisse::where('user_id', $auth->id)
            ->where('caisse_id', $caisse->id)
            ->where('centre_id', $centreId)
            ->where('etat', 'OUVERTE')
            ->latest('ouverture_ts')
            ->first();

        if (!$session) {
            return response()->json([
                'message' => 'Aucune session ouverte trouvée pour cet utilisateur dans ce centre.'
            ], 403);
        }

        foreach ($request->input('regulations') as $reg) {
            // 🔹 Créer le règlement
            $regulation = Regulation::create([
                'facture_id' => $request->input('facture_id'),
                'regulation_method_id' => $reg['method'],
                'amount' => $reg['amount'],
                'date' => now(),
                'type' => $request->input('type'),
                'comment' => $reg['comment'] ?? null,
                'reason' => $reg['reason'] ?? null,
                'phone' => $reg['phone'] ?? null,
                'reference' => $reg['reference'] ?? null,
            ]);

            // 🔹 Créer l'entrée SessionElement
            $sessionElement = \App\Models\SessionElement::create([
                'session_id' => $session->id,
                'facture_id' => $facture->id,
                'montant' => $reg['amount'],
                'regulation_id' => $regulation->id,
                'caisse_id' => $session->caisse_id,
                'created_by' => $auth->id,
                'updated_by' => $auth->id,
                'centre_id' => $centreId,
                'regulation_method_id' => $reg['method'],
            ]);

            $session->increment('solde', $reg['amount']);
            $session->increment('sold_without_small_change', $reg['amount']);
            $session->increment('current_sold', $reg['amount']);
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
        $centreId = $request->header('centre');

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

        $sessionElement = SessionElement::where('regulation_id', $regulation->id)
            ->where('centre_id', $centreId)
            ->first();

        if ($sessionElement) {
            $sessionElement->update([
                'montant' => $regulation->amount,
                'regulation_method_id' => $regulation->regulation_method_id,
            ]);
        }

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
        $auth = $request->user();
        $centreId = $request->header('centre');

        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        // 🔹 Mettre à jour la regulation
        $regulation->update([
            'state' => StatusRegulation::CANCELLED,
            'reason' => $request->input('reason')
        ]);

        // 🔹 Récupérer le SessionElement correspondant au centre
        $sessionElement = \App\Models\SessionElement::where('regulation_id', $regulation->id)
            ->where('centre_id', $centreId)
            ->first();

        if ($sessionElement) {
            $session = \App\Models\SessionCaisse::find($sessionElement->session_id);
            if ($session && $session->centre_id == $centreId) {
                $session->decrement('solde', $sessionElement->montant);
                $session->decrement('current_sold', $sessionElement->montant);
                $session->decrement('sold_without_small_change', $sessionElement->montant);
                Log::info('Solde de la session mis à jour après annulation', [
                    'session_id' => $session->id,
                    'nouveau_solde' => $session->solde - $sessionElement->montant,
                ]);
            }
            $sessionElement->delete();
        }
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
        $centreId = $request->header('centre');
        $request->validate([
            'regulation_method_id' => ['required', 'exists:regulation_methods,id'],
            'amount' => ['required'],
            'amount_waiting' => ['required'],
            'assureur_id' => ['required_if:client_id,null', 'exists:assureurs,id'],
            'client_id' => ['required_if:assureur_id,null', 'exists:clients,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'number_piece' => ['required'],
            'comment' => ['nullable', 'string', 'max:255'],
            'date_piece' => ['required', 'date'],
            'date_reception' => ['required', 'date'],
            'factures' => ['required_if:allFacture,false', 'array'],
            'allFacture' => ['required', 'boolean'],
            'facture_ids' => ['array'],
            'facture_ids.*' => ['exists:factures,id'],
            'factures.*.id' => ['required', 'exists:factures,id'],
            'factures.*.items' => ['array'],
            'factures.*.amount' => ['required'],
            'type' => ['required', 'in:client,assureur'],
            'total_ir_amount' => ['nullable', 'numeric'],
            'ir_rate' => ['nullable', 'numeric'],
            'net_to_pay' => ['required', 'numeric'],

            'factures.*.amount_contested' => ['nullable', 'numeric'],
            'factures.*.amount_paid' => ['nullable', 'numeric'],
            'factures.*.amount_ir' => ['nullable', 'numeric'],
            'factures.*.amount_received' => ['nullable', 'numeric'],
            'factures.*.amount_prorate' => ['nullable', 'numeric'],
            'factures.*.others_amount_excluded' => ['nullable', 'numeric'],
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
                \Log::info('Facturation déjà existante pour cette période, ignorée.');
            }

            SpecialRegulation::create([
                'assureur_id' => $request->input('assureur_id'),
                'centre_id' => $centreId,
                'regulation_id' => $regulateId,
                'regulation_type' => $regulateType,
                'regulation_method_id' => $request->input('regulation_method_id'),
                'amount' => $request->input('amount'),
                'amount_waiting' => $request->input('amount_waiting'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'number_piece' => $request->input('number_piece'),
                'date_piece' => $request->input('date_piece'),
                'date_reception' => $request->input('date_reception'),
            ]);

            FacturationAssurance::create([
                'start_date' => \Carbon\Carbon::parse($request->input('start_date'))->format('Y-m-d H:i:s'),
                'end_date' => \Carbon\Carbon::parse($request->input('end_date'))->format('Y-m-d H:i:s'),
                'assurance' => Assureur::class,
                'facture_number' => $request->input('number_piece'),
                'amount' => $request->input('amount'),
                'price_after_application_hr' => $request->input('total_ir_amount'),
                'price_after_application_tva' => 0,
                'net_to_pay' => $request->input('net_to_pay'),
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

                $processedFactures = [];

                foreach ($prestations as $prestation) {

                    $facture = $prestation->factures()
                        ->where('factures.type', 2)
                        ->where('factures.state', StateFacture::IN_PROGRESS->value)
                        ->first();

                    if (!$facture) {
                        continue;
                    }

                    // ❗ skip si déjà dans la liste ignorée
                    if (in_array($facture->id, $request->input('facture_ids', []))) {
                        continue;
                    }

                    // ❗ anti doublon global
                    if (isset($processedFactures[$facture->id])) {
                        continue;
                    }

                    $this->processFactureRegulation($facture, $request);

                    $this->updatePrestationPivot($prestation,$facture);

                    $processedFactures[$facture->id] = true;
                }
            }

            foreach ($request->input('factures') as $factureData) {
                $facture = Facture::find($factureData['id']);

                if (!$facture) {
                    \Log::warning("Facture introuvable: {$factureData['id']}");
                    continue;
                }

                $this->validatedFacture($facture, true);

                // 🔹 création du règlement
                $facture->regulations()->create([
                    'regulation_method_id' => $request->input('regulation_method_id'),
                    'amount' => $factureData['amount'],
                    'date' => now(),
                    'type' => $request->type === 'client' ? 3 : 2,
                    'comment' => $request->input('comment'),
                    'particular' => true,
                ]);

                // 🔹 mise à jour du state + champs (UNE SEULE FOIS)
                $facture->update(array_merge([
                    'state' => StateFacture::ASSURANCE->value,
                ], [
                    'amount_prorate' => $factureData['amount_prorate'] ?? 0,
                    'amount_contested' => $factureData['amount_contested'] ?? 0,
                    'amount_paid' => $factureData['amount_paid'] ?? 0,
                    'amount_ir' => $factureData['amount_ir'] ?? 0,
                    'amount_received' => $factureData['amount_received'] ?? 0,
                    'others_amount_excluded' => $factureData['others_amount_excluded'] ?? 0,
                ]));

                // 🔹 contentieux
                if (
                    ($request->type === 'client' && $factureData['amount'] < $facture->amount_client) ||
                    ($request->type === 'assureur' && $factureData['amount'] < $facture->amount_pc)
                ) {
                    $facture->update(['contentieux' => true]);
                }

                // 🔹 items update pivot
                foreach ($factureData['items'] ?? [] as $item) {
                    $amount = $item['amount'] * 100;

                    $relation = match ($facture->prestation->type) {
                        TypePrestation::ACTES => $facture->prestation->actes(),
                        TypePrestation::CONSULTATIONS => $facture->prestation->consultations(),
                        TypePrestation::SOINS => $facture->prestation->soins(),
                        TypePrestation::LABORATOIR => $facture->prestation->examens(),
                        TypePrestation::PRODUITS => $facture->prestation->products(),
                        TypePrestation::HOSPITALISATION => $facture->prestation->hospitalisations(),
                        default => null,
                    };

                    if ($relation) {
                        $relation->updateExistingPivot($item['id'], [
                            'amount_regulate' => $amount
                        ]);
                    } else {
                        \Log::warning("Type prestation non géré: {$facture->prestation->type}");
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


    protected function updatePrestationPivot($prestation, $facture): void
    {
        if (!$prestation || !$facture || !$prestation->type) {
            return;
        }

        switch ($prestation->type) {

            case TypePrestation::ACTES:
                $prestation->actes->each(function ($acte) use ($prestation) {
                    $prestation->actes()
                        ->updateExistingPivot(
                            $acte->id,
                            ['amount_regulate' => $acte->pivot->b * $acte->pivot->k_modulateur * 100]
                        );
                });
                break;

            case TypePrestation::CONSULTATIONS:
                $prestation->consultations->each(function ($consultation) use ($prestation) {
                    $prestation->consultations()
                        ->updateExistingPivot(
                            $consultation->id,
                            ['amount_regulate' => $consultation->pivot->pu * 100]
                        );
                });
                break;

            case TypePrestation::SOINS:
                $prestation->soins->each(function ($soins) use ($prestation) {
                    $prestation->soins()
                        ->updateExistingPivot(
                            $soins->id,
                            ['amount_regulate' => $soins->pivot->pu * 100]
                        );
                });
                break;

            case TypePrestation::LABORATOIR:
                $prestation->examens->each(function ($examen) use ($prestation) {
                    $prestation->examens()
                        ->updateExistingPivot(
                            $examen->id,
                            ['amount_regulate' => $examen->pivot->pu * 100]
                        );
                });
                break;

            case TypePrestation::PRODUITS:
                $prestation->products->each(function ($product) use ($prestation) {
                    $prestation->products()
                        ->updateExistingPivot(
                            $product->id,
                            ['amount_regulate' => $product->pivot->pu * 100]
                        );
                });
                break;

            case TypePrestation::HOSPITALISATION:
                $prestation->hospitalisations->each(function ($hospitalisation) use ($prestation) {
                    $prestation->hospitalisations()
                        ->updateExistingPivot(
                            $hospitalisation->id,
                            ['amount_regulate' => $hospitalisation->pivot->pu * 100]
                        );
                });
                break;

            default:
                \Log::warning("Type de prestation non géré: {$prestation->type}");
                break;
        }
    }

    private function processFactureRegulation(Facture $facture, Request $request): void
    {
        if (!$facture || $facture->state === StateFacture::ASSURANCE) {
            return;
        }

        $this->validatedFacture($facture, true);

        $facture->regulations()->create([
            'regulation_method_id' => $request->input('regulation_method_id'),
            'amount' => $facture->amount_pc,
            'date' => now(),
            'type' => $request->type === 'client' ? 3 : 2,
            'comment' => $request->input('comment'),
            'particular' => true,
        ]);

        $facture->update([
            'state' => StateFacture::ASSURANCE->value,
        ]);
    }


    public function updateSpecialRegulationItems(Request $request)
    {
        $request->validate([
            'factures' => ['required', 'array'],
            'factures.*.id' => ['required', 'exists:factures,id'],
            'factures.*.items' => ['required', 'array'],
            'factures.*.items.*.id' => ['required'],
            'factures.*.items.*.amount' => ['required', 'numeric'],
            'factures.*.items.*.amount_prorate' => ['nullable', 'numeric'],
            'factures.*.items.*.amount_contested' => ['nullable', 'numeric'],
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->factures as $factureData) {
                $facture = Facture::find($factureData['id']);
                $this->validatedFacture($facture, true);

                foreach ($factureData['items'] as $item) {
                    $prestation = $facture->prestation;
                    $pivotData = [
                        'amount_regulate' => $item['amount'] * 100,
                        'amount_prorate' => $item['amount_prorate'] ?? 0,
                        'amount_contested' => $item['amount_contested'] ?? 0,
                    ];

                    switch ($prestation->type) {
                        case TypePrestation::ACTES:
                            $prestation->actes()->updateExistingPivot($item['id'], $pivotData);
                            break;
                        case TypePrestation::CONSULTATIONS:
                            $prestation->consultations()->updateExistingPivot($item['id'], $pivotData);
                            break;
                        case TypePrestation::SOINS:
                            $prestation->soins()->updateExistingPivot($item['id'], $pivotData);
                            break;
                        case TypePrestation::LABORATOIR:
                            $prestation->examens()->updateExistingPivot($item['id'], $pivotData);
                            break;
                        case TypePrestation::PRODUITS:
                            $prestation->products()->updateExistingPivot($item['id'], $pivotData);
                            break;
                        case TypePrestation::HOSPITALISATION:
                            $prestation->hospitalisations()->updateExistingPivot($item['id'], $pivotData);
                            break;
                        default:
                            \Log::warning("Type de prestation non implémenté: {$prestation->type}");
                            break;
                    }
                }
            }

            DB::commit();
            return response()->json([
                'message' => 'Montants des items mis à jour avec succès'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], $e->getCode() === 0 ? Response::HTTP_INTERNAL_SERVER_ERROR : Response::HTTP_BAD_REQUEST);
        }
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
        $auth = auth()->user();
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

        $facture->update([
            'state' => $amountValidate
                ? StateFacture::PAID
                : StateFacture::IN_PROGRESS
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



    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @permission RegulationController::get_ventilate_assurance
     * @permission_desc Imprimer les factures des assurances dejà réglées
     */
    public function get_ventilate_assurance(Request $request, $assureur_id)
    {
        try {
            $centreId = $request->header('centre');

            if (!$centreId) {
                return response()->json([
                    'message' => 'Centre non fourni'
                ], 400);
            }

            $request->validate([
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date'],
            ]);

            $query = SpecialRegulation::with([
                'regulationMethod:id,name',
                'assureur',
                'centre',
                'assurance.priseEnCharges.prestations.client',
                'assurance.priseEnCharges.prestations.factures' => function ($q) {
                    $q->where('state', StateFacture::ASSURANCE->value);
                }
            ])
                ->where('assureur_id', $assureur_id)
                ->where('centre_id', $centreId)
                ->whereBetween('created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59'
                ])
                ->whereHas('assurance.priseEnCharges.prestations', function ($p) {
                    $p->whereHas('factures', function ($f) {
                        $f->where('state', StateFacture::ASSURANCE->value);
                    });
                });

            $result = $query->orderBy('created_at', 'ASC')->get();

            if ($result->isEmpty()) {
                return response()->json([
                    'message' => 'Aucune donnée trouvée.'
                ], 404);
            }

            $centre = Centre::find($centreId);
            $media = $centre?->medias()->where('name', 'logo')->first();
            $assureur = Assureur::find($assureur_id);

            $data = [
                'result' => $result,
                'logo' => $media ? 'storage/' . $media->path . '/' . $media->filename : '',
                'centre' => $centre,
                'assureur' => $assureur,
                'start' => $request->start_date,
                'end' => $request->end_date
            ];
            $fileName = 'etats-des-factures-assurances-reglees' . now()->format('YmdHis') . '.pdf';
            $folderPath = 'storage/etats-des-factures-assurances-reglees';
            $filePath = $folderPath . '/' . $fileName;

            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0755, true);
            }
            save_browser_shot_pdf(
                view: 'pdfs.etats-des-factures-assurances-reglees.etats-des-factures-assurances-reglees',
                data: $data,
                folderPath: $folderPath,
                path: $filePath,
                margins: [15, 10, 15, 10],
                footer: 'pdfs.reports.factures.footer',
                format: 'A5',
                direction: 'landscape'
            );

            if (!file_exists($filePath)) {
                return response()->json([
                    'message' => 'Le fichier PDF n\'a pas été généré.'
                ], 500);
            }

            // 🔹 Encodage
            $pdfContent = file_get_contents($filePath);
            $base64 = base64_encode($pdfContent);

            return response()->json([
                'result' => $result,
                'base64' => $base64,
                'url' => $filePath,
                'filename' => $fileName,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'error' => 'Erreur de validation',
                'messages' => $e->errors()
            ], 422);

        } catch (\Exception $e) {

            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }


}
