<?php

namespace App\Http\Controllers;

use App\Enums\StateFacture;
use App\Enums\TypePrestation;
use App\Http\Requests\PrestationRequest;
use App\Models\Acte;
use App\Models\Assureur;
use App\Models\Centre;
use App\Models\Client;
use App\Models\Consultation;
use App\Models\Facture;
use App\Models\FactureAssociate;
use App\Models\Media;
use App\Models\Prestation;
use App\Models\PriseEnCharge;
use App\Models\RegulationMethod;
use App\Models\Soins;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PHPUnit\Framework\Exception;
use Spatie\Browsershot\Exceptions\CouldNotTakeBrowsershot;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PrestationController extends Controller
{
    public function typePrestation()
    {
        return TypePrestation::toArray();
    }

    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @permission PrestationController::index
     * @permission_desc Afficher la liste des prestations
     */
    public function index(Request $request)
    {
        $prestations = Prestation::with([
            'createdBy:id,nom_utilisateur',
            'updatedBy:id,nom_utilisateur',
            'payableBy',
            'client',
            'consultant:id,nomcomplet,code_specialite',
            'consultant.code_specialite:id,nom_specialite',
            'priseCharge',
            'priseCharge.assureur',
            'actes',
            'soins',
            'consultations',
            'centre',
            'factures',
            'factures.regulations',
            'factures.regulations.regulationMethod',
        ])->when($request->input('client_id'), function ($query) use ($request) {
            $query->where('client_id', $request->input('client_id'));
        })->when($request->input('consultant_id'), function ($query) use ($request) {
            $query->where('consultant_id', $request->input('consultant_id'));
        })->when($request->input('type'), function ($query) use ($request) {
            $query->where('type', $request->input('type'));
        })->when($request->input('mode_paiement'), function ($query) use ($request) {
            if ($request->input('mode_paiement') == 'client-tiers') {
                $query->whereNotNull('payable_by');
            }

            if ($request->input('mode_paiement') == 'assurance') {
                $query->whereNotNull('prise_charge_id');
            }

            if ($request->input('mode_paiement') == 'lui-meme') {
                $query->whereNull('payable_by')->whereNull('prise_charge_id');
            }
        })->when($request->input('programmation_date_start') && $request->input('programmation_date_end'), function (Builder $query) use ($request) {
            $startDate = $request->input('programmation_date_start');
            $endDate = $request->input('programmation_date_end');
            if ($startDate && $endDate) {
                $query->whereBetween('programmation_date', [$startDate, $endDate]);
            }
        })->when($request->input('order'), function (Builder $query) use ($request) {
            $query->orderBy($request->input('order')['column'], $request->input('order')['direction']);
        }, function (Builder $query) {
            $query->latest();
        })->when($request->has('regulated'), function (Builder $query) use ($request) {
            if (is_array($request->input('regulated'))) {
                $query->whereIn('regulated', $request->input('regulated'));
            } else {
                $query->where('regulated', $request->input('regulated'));
            }
        })->when($request->input('factures_created_at'), function ($query) use ($request) {
            $query->whereHas('factures', function ($query) use ($request) {
                $query->whereDate('factures.created_at', $request->input('factures_created_at'));
            });
        })->when($request->input('created_at'), function (Builder $query) use ($request) {
            $query->whereDate('created_at', $request->input('created_at'));
        })->where('centre_id', $request->header('centre'))
            ->paginate(
                perPage: $request->input('per_page', 25),
                page: $request->input('page', 1)
            );


        return response()->json([
            'prestations' => $prestations,
            'regulation_methods' => RegulationMethod::get()->toArray(),
        ]);
    }

    /**
     * @param PrestationRequest $request
     * @return JsonResponse
     *
     * @permission PrestationController::store
     * @permission_desc Enregistrer une prestation
     * @throws \Throwable
     */
    public function store(PrestationRequest $request)
    {
        DB::beginTransaction();
        try {
            if ($errorConflit = $request->validateRdvDate()) {
                return response()->json($errorConflit, Response::HTTP_CONFLICT);
            }

            $centre = $request->header('centre');
            $data = array_merge($request->except(['actes']), ['centre_id' => $centre]);

            // Si le montant de la remise + la prise en charge est supérieur au montant de la prestation alors cette prestation passe en état encours
            $data = $this->getDataForPriseEnCharge($request, $data);

            $prestation = Prestation::create($data);
            $this->attachElementWithPrestation($request, $prestation);

            // Si le montant de la remise + la prise en charge est égal au montant de la prestation alors on crée la facture
            if ($data['regulated'] == 2 || $data['payable_by']) {
                save_facture($prestation, $centre, 2);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], $e->getCode() === 0 ? Response::HTTP_INTERNAL_SERVER_ERROR : Response::HTTP_BAD_REQUEST);
        }
        DB::commit();

        return response()->json([
            'message' => __("Prestation ajoutée avec succès !")
        ]);
    }

    /**
     * @param Prestation $prestation
     * @return JsonResponse
     */
    public function show(Prestation $prestation)
    {
        return response()->json([
            'prestation' => $prestation->load([
                'payableBy:id,nomcomplet_client',
                'client',
                'consultant:id,nomcomplet',
                'priseCharge:id,assureur_id,taux_pc',
                'priseCharge.assureur:id,nom',
                'actes',
                'soins',
                'consultations',
            ])
        ]);
    }

    /**
     * @param PrestationRequest $request
     * @param Prestation $prestation
     * @return JsonResponse
     *
     * @permission PrestationController::update
     * @permission_desc Modifier une prestation
     * @throws \Throwable
     */
    public function update(PrestationRequest $request, Prestation $prestation)
    {
        DB::beginTransaction();
        try {
            if ($errorConflit = $request->validateRdvDate($prestation->id)) {
                return response()->json($errorConflit, Response::HTTP_CONFLICT);
            }

            $data = $request->validated();

            // Si le montant de la remise + la prise en charge est supérieur au montant de la prestation alors cette prestation passe en état encours
            $data = $this->getDataForPriseEnCharge($request, $data);

            $prestation->update($data);
            $this->attachElementWithPrestation($request, $prestation, true);

            if ($data['regulated'] == 2) {
                save_facture($prestation, $prestation->centre->id, 2);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], $e->getCode() === 0 ? Response::HTTP_INTERNAL_SERVER_ERROR : Response::HTTP_BAD_REQUEST);
        }
        DB::commit();

        return response()->json([
            'message' => __("Prestation modifiée avec succès !")
        ]);
    }

    /**
     * @param Prestation $prestation
     * @param Request $request
     * @return JsonResponse
     * @throws \Throwable
     *
     * @permission PrestationController::saveFacture
     * @permission_desc Enregistrer une facture
     */
    public function saveFacture(Prestation $prestation, Request $request)
    {
        $request->validate([
            'proforma' => 'required|in:1,2',
        ]);

        if ($prestation->regulated == 2) {
            return response()->json([
                'message' => __("Cette prestation est déjà réglée !")
            ], Response::HTTP_BAD_REQUEST);
        }

        DB::beginTransaction();
        try {
            $centre = $request->header('centre');
            $facture = save_facture($prestation, $centre, $request->input('proforma'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => $th->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
        DB::commit();

        return response()->json([
            'message' => "Facture enregistrée avec succès !",
            'facture' => $facture
        ]);
    }

    /**
     * @param Prestation $prestation
     * @param Request $request
     *
     * @return JsonResponse
     * @permission PrestationController::changeState
     * @permission_desc Changer l’état d’une prestation
     */
    public function changeState(Prestation $prestation, Request $request)
    {
        $request->validate([
            'state' => 'required|in:1,2,3',
        ]);

        $prestation->update(['regulated' => $request->state]);

        return response()->json([
            'message' => __("L'état de la prestation a été modifié avec succès !")
        ], 202);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @permission PrestationController::getFacturesInProgress
     * @permission_desc Récupérer les factures non réglées par assurance ou par client associé
     */
    public function getFacturesInProgress(Request $request)
    {
        $request->validate([
            'assurance' => ['', 'exists:assureurs,id'],
            'payable_by' => ['', 'exists:clients,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
        ]);

        $prestations = [];
        $clients = [];
        $lastFactures = false;
        $dateLatestFacture = '';
        $totalAmount = 0;

        if ($request->input('assurance')) {
            $latestPrestations = Prestation::filterInProgress(
                startDate: $request->input('start_date'),
                endDate: $request->input('end_date'),
                assurance: $request->input('assurance'),
                latestFacture: true
            )->paginate(
                perPage: $request->input('per_page', 25),
                page: $request->input('page', 1)
            );

            if (! $latestPrestations->items()) {
                $prestations = Prestation::filterInProgress(
                    startDate: $request->input('start_date'),
                    endDate: $request->input('end_date'),
                    assurance: $request->input('assurance')
                )
                ->paginate(
                    perPage: $request->input('per_page', 25),
                    page: $request->input('page', 1)
                );
            }
            else {
                $lastFactures = true;
                $dateLatestFacture = $latestPrestations->first()->factures->first()->date_fact;
                $prestations = $latestPrestations;
            }
        }

        if ($request->input('payable_by')) {
            $clients = Client::with([
                'toPay' => function ($query) use ($request) {
                    $query->whereHas('factures', function ($query) use ($request) {
                        $query->where('factures.type', 2)
                            ->where('factures.state', StateFacture::IN_PROGRESS->value)
                            ->whereBetween('factures.date_fact', [$request->input('start_date'), $request->input('end_date')]);
                    });
                },
                'toPay.factures' => function ($query) use ($request) {
                    $query->where('factures.type', 2);
                },
                'toPay.actes',
                'toPay.soins',
                'toPay.client:id,nom_cli,prenom_cli,nomcomplet_client,ref_cli,date_naiss_cli'
            ])
                ->whereHas('toPay', function ($query) use ($request) {
                    $query->whereHas('factures', function ($query) use ($request) {
                        $query->where('factures.type', 2)
                            ->where('factures.state', StateFacture::IN_PROGRESS->value)
                            ->whereBetween('factures.date_fact', [$request->input('start_date'), $request->input('end_date')]);
                    });
                })
                ->select('clients.*')
                ->selectSub(function ($query) use ($request) {
                    $query->from('factures')
                        ->join('prestations', 'prestations.id', '=', 'factures.prestation_id')
                        ->whereColumn('prestations.payable_by', 'clients.id')
                        ->where('factures.type', 2)
                        ->where('factures.state', StateFacture::IN_PROGRESS->value)
                        ->whereBetween('factures.date_fact', [$request->input('start_date'), $request->input('end_date')])
                        ->selectRaw('SUM(factures.amount_client) / 100');
                }, 'total_amount')
                ->whereTypeCli('associate')
                ->get();
        }

        $totalAmount = DB::table('factures')
            ->join('prestations', 'factures.prestation_id', '=', 'prestations.id')
            ->where('factures.type', 2)
            ->where('factures.state', StateFacture::IN_PROGRESS->value)
            ->when($lastFactures, fn($query) => $query->whereDate('factures.date_fact', '<', $request->input('start_date')))
            ->where('prestations.centre_id', $request->header('centre'))
            ->when($request->input('assurance'), fn($q) => $q->where('prise_en_charges.assureur_id', $request->input('assurance')))
            ->join('prise_en_charges', 'prestations.prise_charge_id', '=', 'prise_en_charges.id')
            ->selectRaw('SUM(factures.amount_pc) / 100 as total')
            ->value('total');

        return response()->json([
            'prestations' => $prestations,
            'clients' => $clients,
            'regulation_methods' => RegulationMethod::get()->toArray(),
            'last_factures' => $lastFactures,
            'date_latest_facture' => $dateLatestFacture,
            'total_amount' => $totalAmount
        ]);
    }

    protected function attachElementWithPrestation(PrestationRequest $request, Prestation $prestation, bool $update = false)
    {
        // ToDo: récupérer la durée d'une prestation dans la table Settings (en Heure).
        $prestationDuration = 1;

        switch ($request->type) {
            case TypePrestation::ACTES->value:
                if ($update) {
                    $prestation->actes()->detach();
                }

                foreach ($request->post('actes') as $item) {
                    $acte = Acte::find($item['id']);

                    $b = $acte->b;
                    $kModulateur = $acte->k_modulateur;
                    if ($prestation->priseCharge && $actePc = $prestation->priseCharge->assureur->actes()->find($acte->id)) {
                        $b = $actePc->pivot->b;
                        $kModulateur = $actePc->pivot->k_modulateur;
                    }

                    $prestation->actes()->attach($item['id'], [
                        'remise' => $item['remise'],
                        'quantity' => $item['quantity'],
                        'date_rdv' => $item['date_rdv'],
                        'date_rdv_end' => Carbon::createFromTimeString($item['date_rdv'])->addHours($prestationDuration),
                        'b' => $b,
                        'k_modulateur' => $kModulateur,
                        'pu' => $prestation->priseCharge ? $b * $kModulateur : $acte->pu
                    ]);
                }
                break;
            case TypePrestation::SOINS->value:
                if ($update) {
                    $prestation->soins()->detach();
                }

                foreach ($request->post('soins') as $item) {
                    $soin = Soins::find($item['id']);
                    $pu = $soin->pu;
                    if ($prestation->priseCharge) {
                        if ($soinPc = $prestation->priseCharge->assureur->soins()->find($soin->id)) {
                            $pu = $soinPc->pivot->pu;
                        } else {
                            $pu = $soin->pu_default;
                        }
                    }

                    $prestation->soins()->attach($item['id'], [
                        'remise' => $item['remise'],
                        'nbr_days' => $item['nbr_days'],
                        'type_salle' => $item['type_salle'],
                        'honoraire' => $item['honoraire'],
                        'pu' => $pu
                    ]);
                }
                break;
            case TypePrestation::CONSULTATIONS->value:
                if ($update) {
                    $prestation->consultations()->detach();
                }

                foreach ($request->post('consultations') as $item) {
                    $consultation = Consultation::find($item['id']);
                    $pu = $consultation->pu;
                    if ($prestation->priseCharge) {
                        if ($consultationPC = $prestation->priseCharge->assureur->consultations()->find($consultation->id)) {
                            $pu = $consultationPC->pivot->pu;
                        } else {
                            $pu = $consultation->pu_default;
                        }
                    }

                    $prestation->consultations()->attach($item['id'], [
                        'date_rdv' => $item['date_rdv'],
                        'remise' => $item['remise'],
                        'quantity' => $item['quantity'],
                        'date_rdv_end' => Carbon::createFromTimeString($item['date_rdv'])->addHours($prestationDuration),
                        'pu' =>  $pu
                    ]);
                }
                break;
            default:
                throw new Exception("Ce type de prestation n'est pas encore implémenté", Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @param PrestationRequest $request
     * @param mixed $data
     * @return mixed
     *
     * @permission PrestationController::getDataForPriseEnCharge
     * @permission_desc Récupérer les données pour la prise en charge
     */
    public function getDataForPriseEnCharge(PrestationRequest $request, mixed &$data): mixed
    {
        if ($request->input('prise_charge_id')) {
            $amount = 0;
            $amount_pc = 0;
            $amount_remise = 0;
            $priseCharge = PriseEnCharge::find($request->input('prise_charge_id'));

            if ($priseCharge->usage_unique) {
                $priseCharge->update(['used' => true]);
            }

            foreach ($request->input('actes') as $acteData) {
                if ($acte = $priseCharge->assureur->actes()->find($acteData['id'])) {
                    $pu = $acte->pivot->b * $acte->pivot->k_modulateur;
                } else {
                    $acte = Acte::find($acteData['id']);
                    $pu = $acte->b * $acte->k_modulateur;
                }

                $amount_acte_pc = ($acteData['quantity'] * $pu * $priseCharge->taux_pc) / 100;
                $amount_pc += $amount_acte_pc;

                $amount_acte_remise = ($acteData['quantity'] * $pu * $acteData['remise']) / 100;
                $amount_remise += $amount_acte_remise;

                $amount += $acteData['quantity'] * $pu;
            }

            foreach ($request->input('soins') as $soinData) {
                if ($soins = $priseCharge->assureur->soins()->find($soinData['id'])) {
                    $pu = $soins->pivot->pu;
                } else {
                    $soin = Soins::find($soinData['id']);
                    $pu = $soin->pu;
                }

                $amount_acte_pc = ($soinData['nbr_days'] * $pu * $priseCharge->taux_pc) / 100;
                $amount_pc += $amount_acte_pc;

                $amount_acte_remise = ($soinData['nbr_days'] * $pu * $soinData['remise']) / 100;
                $amount_remise += $amount_acte_remise;

                $amount += $soinData['nbr_days'] * $pu;
            }

            foreach ($request->input('consultations') as $consultationData) {
                if ($consultation = $priseCharge->assureur->consultations()->find($consultationData['id'])) {
                    $pu = $consultation->pivot->pu;
                } else {
                    $consultation = Consultation::find($consultationData['id']);
                    $pu = $consultation->pu;
                }

                $amount_acte_pc = ($consultationData['quantity'] * $pu * $priseCharge->taux_pc) / 100;
                $amount_pc += $amount_acte_pc;

                $amount_acte_remise = ($consultationData['quantity'] * $pu * $consultationData['remise']) / 100;
                $amount_remise += $amount_acte_remise;

                $amount += $consultationData['quantity'] * $pu;
            }

            $data['regulated'] = $amount == ($amount_remise + $amount_pc) ? 2 : 0;
            $data['amount_pc'] = $amount_pc;
            $data['amount_remise'] = $amount_remise;
            $data['amount'] = $amount;
        } else {
            $data['regulated'] = $data['payable_by'] ? 1 : 0;
        }
        return $data;
    }

    /**
     * Generates and returns the URL of a PDF invoice for an insurance company.
     *
     * This function validates the request parameters, generates a PDF invoice for the specified
     * insurance company and date range, and returns the URL of the generated PDF. If the PDF
     * already exists, it retrieves the existing file URL instead of generating a new one.
     *
     * @param Request $request The HTTP request object containing the input data.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response containing the URL of the generated or existing PDF invoice.
     *
     * @throws \Throwable If an error occurs during the PDF generation process.
     *
     * @permission PrestationController::printFactureAssurance
     * @permission_desc Imprimer la facture d'assurance
     */
    public function printFactureAssurance(Request $request)
    {
        $request->validate([
            'assurance' => ['required', 'exists:assureurs,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'actualize' => ["boolean"]
        ]);
        $assurance = Assureur::find($request->assurance);
        $startDate = Carbon::createFromTimeString($request->input("start_date"));
        $endDate = Carbon::createFromTimeString($request->input("end_date"));
        $fileName = $assurance->ref .'-'. $startDate->format('d-m-Y')  .'-'. $endDate->format('d-m-Y') . '.pdf';

        $mediaFacture = Media::where('filename', $fileName)->first();

        if ($mediaFacture) {
            $path = $mediaFacture->path;
        }
        else {
            $priseEnCharges = PriseEnCharge::filterFactureInProgress(
                startDate: $request->input('start_date'),
                endDate: $request->input('end_date'),
                assurance: $request->input('assurance')
            )->get();
            $centre = Centre::find($request->header('centre'));
            $code = Str::padLeft((FactureAssociate::max('id') ? FactureAssociate::max('id') + 1 : 1), 4, 0) .'/'. Str::upper($centre->reference) .'/'. now()->format('y');
            $media = $centre->medias()->where('name', 'logo')->first();

            $data = [
                'assurance' => $assurance,
                'priseEnCharges' => $priseEnCharges,
                'code' => $code,
                'centre' => $centre,
                'logo' => $media ? 'storage/'. $media->path .'/'. $media->filename : '',
                "start_date" => $startDate,
                "end_date" => $endDate,
            ];

            try {
                save_browser_shot_pdf(
                    view: 'pdfs.factures.assurances.facture-assurance',
                    data: $data,
                    folderPath: 'storage/facture-assurance',
                    path: 'storage/facture-assurance/' . $fileName,
                    footer: 'pdfs.factures.assurances.footer',
                    margins: [15, 10, 15, 10]
                );
            }
            catch (CouldNotTakeBrowsershot|Throwable $e) {
                Log::error($e->getMessage());

                return \response()->json([
                    'message' => __("Un erreur inattendue est survenu.")
                ], 400);
            }

            $path = 'facture-assurance/' . $fileName;

            $facture = $assurance->factures()->create([
                'start_date' =>  $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'code' => $code,
                'amount' => $priseEnCharges->first()->total_amount,
                'date' => now(),
            ]);

            $facture->medias()->create([
                'name' => "FACTURE-ASSOCIATE",
                'disk' => 'public',
                'path' => $path,
                'filename' => $fileName,
                'mimetype' => 'pdf',
                'extension' => 'pdf',
            ]);
        }

        $pdfContent = file_get_contents('storage/facture-assurance/' . $fileName);
        $base64 = base64_encode($pdfContent);

        return response()->json([
            'base64' => $base64,
            'filename' => $fileName
        ]);
    }
}
