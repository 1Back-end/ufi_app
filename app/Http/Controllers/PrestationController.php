<?php

namespace App\Http\Controllers;

use App\Enums\StateFacture;
use App\Enums\TypePrestation;
use App\Http\Requests\PrestationRequest;
use App\Models\Acte;
use App\Models\Assureur;
use App\Models\Centre;
use App\Models\Client;
use App\Models\Consultant;
use App\Models\Consultation;
use App\Models\ConventionAssocie;
use App\Models\Examen;
use App\Models\Facture;
use App\Models\FactureAssociate;
use App\Models\Media;
use App\Models\OpsTblHospitalisation;
use App\Models\Prestation;
use App\Models\PriseEnCharge;
use App\Models\Product;
use App\Models\RegulationMethod;
use App\Models\RendezVous;
use App\Models\Setting;
use App\Models\Soins;
use App\Notifications\SendRdvNotification;
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
            'priseCharge.quotation',
            'actes',
            'soins',
            'consultations',
            'hospitalisations',
            'products',
            'examens',
            'examens.kbPrelevement',
            'examens.typePrelevement',
            'examens.paillasse',
            'examens.subFamilyExam',
            'examens.subFamilyExam.familyExam',
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
        $centre = $request->header('centre');
        $data = array_merge($request->validated(), ['centre_id' => $centre]);

        DB::beginTransaction();
        try {
            if ($errorConflit = $request->validateRdvDate()) {
                return response()->json($errorConflit, Response::HTTP_CONFLICT);
            }

            // Si le montant de la remise + la prise en charge est supérieur au montant de la prestation alors cette prestation passe en état encours
            $data = $this->getDataForPriseEnCharge($request, $data);

            if ($data['payable_by']) {
                $convention = ConventionAssocie::query()
                    ->whereClientId($data['payable_by'])
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->whereColumn('amount', '<=', 'amount_max')
                    ->first();

                if ($convention && ($convention->amount + $data['amount']) > $convention->amount_max) {
                    throw new \Exception('Le plafond de la convention est dépassé de : ' . $convention->amount + $data['amount'] - $convention->amount_max . "FCFA", 400);
                }

                $data['convention_id'] = $convention->id;
            }

            $prestation = Prestation::create(\Arr::except($data, ['payable_by_file_update', 'payable_by_file', 'actes', 'amount_pc', 'amount_remise', 'amount']));

            $this->attachElementWithPrestation($request, $prestation);

            // Si le montant de la remise + la prise en charge est égal au montant de la prestation alors on crée la facture
            if ($data['regulated'] == 2 || $data['payable_by']) {
                save_facture($prestation, $centre, 2);
            }

            // Save File for associate client !
            if ($data['payable_by'] && $request->file('payable_by_file')) {
                upload_media(
                    model: $prestation,
                    file: $request->file('payable_by_file'),
                    name: 'prestations-client-associate',
                    disk: 'public',
                    path: 'prestations/client-associate',
                    filename: $prestation->client->ref_cli . "-" . $prestation->id,
                );
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
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
                'priseCharge',
                'priseCharge.assureur',
                'priseCharge.quotation',
                'actes',
                'soins',
                'consultations',
                'medias',
                'hospitalisations',
                'products',
                'examens',
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

            if ($data['payable_by']) {
                $convention = ConventionAssocie::query()
                    ->whereClientId($data['payable_by'])
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->whereColumn('amount', '<=', 'amount_max')
                    ->first();

                if ($convention && ($convention->amount + $data['amount']) > $convention->amount_max) {
                    throw new \Exception('Le plafond de la convention est dépassé de : ' . $convention->amount + $data['amount'] - $convention->amount_max . "FCFA", 400);
                }

                $data['convention_id'] = $convention->id;
            }

            $prestation->update($data);
            $this->attachElementWithPrestation($request, $prestation, true);

            if ($data['regulated'] == 2) {
                save_facture($prestation, $prestation->centre->id, 2);
            }

            // Save File for associate client !
            if ($data['payable_by'] && $request->file('payable_by_file') && $request->input('payable_by_file_update')) {
                upload_media(
                    model: $prestation,
                    file: $request->file('payable_by_file'),
                    name: 'prestations-client-associate',
                    disk: 'public',
                    path: 'prestations/client-associate',
                    filename: $prestation->client->ref_cli . "-" . $prestation->id,
                    update: $prestation->medias()->where('name', 'prestations-client-associate')->first()
                );
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

        $lastFactures = false;
        $dateLatestFacture = '';

        $latestPrestations = Prestation::filterInProgress(
            startDate: $request->input('start_date'),
            endDate: $request->input('end_date'),
            assurance: $request->input('assurance'),
            payableBy: $request->input('payable_by'),
            latestFacture: true
        )->paginate(
            perPage: $request->input('per_page', 25),
            page: $request->input('page', 1)
        );

        if (! $latestPrestations->items()) {
            $prestations = Prestation::filterInProgress(
                startDate: $request->input('start_date'),
                endDate: $request->input('end_date'),
                assurance: $request->input('assurance'),
                payableBy: $request->input('payable_by'),
                search: $request->input('search'),
            )
                ->paginate(
                    perPage: $request->input('per_page', 25),
                    page: $request->input('page', 1)
                );
        } else {
            $lastFactures = true;
            $dateLatestFacture = $latestPrestations->first()->factures->first()->date_fact;
            $prestations = $latestPrestations;
        }

        $column = $request->input('assurance') ? 'amount_pc' : 'amount_client';
        $totalAmount = DB::table('factures')
            ->join('prestations', 'factures.prestation_id', '=', 'prestations.id')
            ->where('factures.type', 2)
            ->where('factures.state', StateFacture::IN_PROGRESS->value)
            ->when($lastFactures, fn($query) => $query->whereDate('factures.date_fact', '<', $request->input('start_date')))
            ->where('prestations.centre_id', $request->header('centre'))
            ->when($request->input('assurance'), fn($q) => $q->where('prise_en_charges.assureur_id', $request->input('assurance')))
            ->when($request->input('payable_by'), fn($q) => $q->where('prestations.payable_by', $request->input('payable_by')))
            ->when($request->input('payable_by'), fn($q) => $q->join('clients', 'prestations.payable_by', '=', 'clients.id'))
            ->when($request->input('assurance'), fn($q) => $q->join('prise_en_charges', 'prestations.prise_charge_id', '=', 'prise_en_charges.id'))
            ->selectRaw("SUM(factures.$column) / 100 as total")
            ->value('total');

        return response()->json([
            'prestations' => $prestations,
            'regulation_methods' => RegulationMethod::get()->toArray(),
            'last_factures' => $lastFactures,
            'date_latest_facture' => $dateLatestFacture,
            'total_amount' => round($totalAmount, 2)
        ]);
    }

    protected function attachElementWithPrestation(PrestationRequest $request, Prestation $prestation, bool $update = false)
    {
        $prestationDuration = Setting::where('key', 'rdv_duration')->first()->value;

        if ($update) {
            $prestation->appointments()->delete();
        }

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
                        'date_rdv_end' => Carbon::createFromTimeString($item['date_rdv'])->addMinutes((int)$prestationDuration),
                        'b' => $b,
                        'k_modulateur' => $kModulateur,
                        'pu' => $prestation->priseCharge ? $b * $kModulateur : $acte->pu
                    ]);

                    $this->createRdv($prestation, $request->input('consultant_id'), $request->input('client_id'), $item['date_rdv']);
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
                        'date_rdv_end' => Carbon::createFromTimeString($item['date_rdv'])->addMinutes((int)$prestationDuration),
                        'pu' =>  $pu
                    ]);

                    $this->createRdv($prestation, $request->input('consultant_id'), $request->input('client_id'), $item['date_rdv']);
                }
                break;
            case TypePrestation::HOSPITALISATION->value:
                if ($update) {
                    $prestation->hospitalisations()->detach();
                }

                foreach ($request->post('hospitalisations') as $item) {
                    $hospitalisation = OpsTblHospitalisation::find($item['id']);
                    $pu = $hospitalisation->pu;
                    if ($prestation->priseCharge) {
                        if ($hospitalisationPC = $prestation->priseCharge->assureur->hospitalisations()->find($hospitalisation->id)) {
                            $pu = $hospitalisationPC->pivot->pu;
                        } else {
                            $pu = $hospitalisation->pu_default;
                        }
                    }

                    $prestation->hospitalisations()->attach($item['id'], [
                        'date_rdv' => $item['date_rdv'],
                        'remise' => $item['remise'],
                        'quantity' => $item['quantity'],
                        'date_rdv_end' => Carbon::createFromTimeString($item['date_rdv'])->addMinutes((int)$prestationDuration),
                        'pu' =>  $pu
                    ]);

                    $this->createRdv($prestation, $request->input('consultant_id'), $request->input('client_id'), $item['date_rdv']);
                }
                break;
            case TypePrestation::LABORATOIR->value:
                if ($update) {
                    $prestation->examens()->detach();
                }

                foreach ($request->input('examens') as $item) {
                    $examen = Examen::find($item['id']);
                    $pu = $examen->price;
                    if ($prestation->priseCharge) {
                        if ($examenPC = $prestation->priseCharge->assureur->examens()->find($examen->id)) {
                            $pu = $examenPC->pivot->b * $prestation->priseCharge->quotation->taux;
                        } else {
                            $pu = ($prestation->priseCharge && $prestation->priseCharge->assureur->BM ? $examen->b1 : $examen->b) * $prestation->priseCharge->quotation->taux;
                        }
                    }

                    $prestation->examens()->attach($item['id'], [
                        'remise' => $item['remise'],
                        'quantity' => $item['quantity'],
                        'pu' =>  $pu,
                        'b' => $prestation->priseCharge && $prestation->priseCharge->assureur->BM ? $examen->b1 : $examen->b,
                    ]);
                }
                break;
            case TypePrestation::PRODUITS->value:
                if ($update) {
                    $prestation->products()->detach();
                }

                foreach ($request->post('products') as $item) {
                    $product = Product::find($item['id']);
                    $pu = $product->price;

                    $prestation->products()->attach($item['id'], [
                        'remise' => $item['remise'],
                        'quantity' => $item['quantity'],
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
        $amount = 0;
        $amount_pc = 0;
        $amount_remise = 0;
        $requestData = $request->all();

        $priseCharge = null;
        if ($request->input('prise_charge_id')) {
            $priseCharge = PriseEnCharge::find($request->input('prise_charge_id'));

            if ($priseCharge->usage_unique) {
                $priseCharge->update(['used' => true]);
            }
        }

        if (isset($requestData['actes']) && $requestData['actes']) {
            foreach ($request->input('actes') as $acteData) {
                if ($priseCharge) {
                    if ($acte = $priseCharge->assureur->actes()->find($acteData['id'])) {
                        $pu = $acte->pivot->b * $acte->pivot->k_modulateur;
                    } else {
                        $acte = Acte::find($acteData['id']);
                        $pu = $acte->b * $acte->k_modulateur;
                    }

                    $amount_acte_pc = ($acteData['quantity'] * $pu * $priseCharge->taux_pc) / 100;
                    $amount_pc += $amount_acte_pc;
                } else {
                    $acte = Acte::find($acteData['id']);
                    $pu = $acte->pu;
                }

                $amount_acte_remise = ($acteData['quantity'] * $pu * $acteData['remise']) / 100;
                $amount_remise += $amount_acte_remise;

                $amount += $acteData['quantity'] * $pu;
            }
        }

        if (isset($requestData['soins']) && $requestData['soins']) {
            foreach ($request->input('soins') as $soinData) {
                if ($priseCharge) {
                    if ($soins = $priseCharge->assureur->soins()->find($soinData['id'])) {
                        $pu = $soins->pivot->pu;
                    } else {
                        $soin = Soins::find($soinData['id']);
                        $pu = $soin->pu_default;
                    }

                    $amount_soin_pc = ($soinData['nbr_days'] * $pu * $priseCharge->taux_pc) / 100;
                    $amount_pc += $amount_soin_pc;
                } else {
                    $soin = Soins::find($soinData['id']);
                    $pu = $soin->pu;
                }

                $amount_soin_remise = ($soinData['nbr_days'] * $pu * $soinData['remise']) / 100;
                $amount_remise += $amount_soin_remise;

                $amount += $soinData['nbr_days'] * $pu;
            }
        }

        if (isset($requestData['consultations']) && $requestData['consultations']) {
            foreach ($request->input('consultations') as $consultationData) {
                if ($priseCharge) {
                    if ($consultation = $priseCharge->assureur->consultations()->find($consultationData['id'])) {
                        $pu = $consultation->pivot->pu;
                    } else {
                        $consultation = Consultation::find($consultationData['id']);
                        $pu = $consultation->pu_default;
                    }

                    $amount_consultation_pc = ($consultationData['quantity'] * $pu * $priseCharge->taux_pc) / 100;
                    $amount_pc += $amount_consultation_pc;
                } else {
                    $consultation = Consultation::find($consultationData['id']);
                    $pu = $consultation->pu;
                }

                $amount_consultation_remise = ($consultationData['quantity'] * $pu * $consultationData['remise']) / 100;
                $amount_remise += $amount_consultation_remise;

                $amount += $consultationData['quantity'] * $pu;
            }
        }

        if (isset($requestData['hospitalisations']) && $requestData['hospitalisations']) {
            foreach ($request->input('hospitalisations') as $hospitalisationData) {
                if ($priseCharge) {
                    if ($hospitalisation = $priseCharge->assureur->hospitalisations()->find($hospitalisationData['id'])) {
                        $pu = $hospitalisation->pivot->pu;
                    } else {
                        $hospitalisation = OpsTblHospitalisation::find($hospitalisationData['id']);
                        $pu = $hospitalisation->pu_default;
                    }

                    $amount_examen_pc = ($hospitalisationData['quantity'] * $pu * $priseCharge->taux_pc) / 100;
                    $amount_pc += $amount_examen_pc;
                } else {
                    $hospitalisation = OpsTblHospitalisation::find($hospitalisationData['id']);
                    $pu = $hospitalisation->pu;
                }

                $amount_examen_remise = ($hospitalisationData['quantity'] * $pu * $hospitalisationData['remise']) / 100;
                $amount_remise += $amount_examen_remise;

                $amount += $hospitalisationData['quantity'] * $pu;
            }
        }

        if (isset($requestData['products']) && $requestData['products']) {
            foreach ($request->input('products') as $productData) {
                $product = Product::find($productData['id']);
                $pu = $product->price;

                $amount_product_remise = ($productData['quantity'] * $pu * $productData['remise']) / 100;
                $amount_remise += $amount_product_remise;

                $amount += $productData['quantity'] * $pu;
            }
        }

        if (isset($requestData['examens']) && $requestData['examens']) {
            foreach ($request->input('examens') as $examenData) {
                if ($priseCharge) {
                    if ($examen = $priseCharge->assureur->examens()->find($examenData['id'])) {
                        $pu = $examen->pivot->b * $priseCharge->quotation->taux;
                    } else {
                        $examen = Examen::find($examenData['id']);
                        $pu = $examen->b * $priseCharge->quotation->taux;
                    }

                    $amount_examen_pc = ($examenData['quantity'] * $pu * $priseCharge->taux_pc) / 100;
                    $amount_pc += $amount_examen_pc;
                } else {
                    $examen = Examen::find($examenData['id']);
                    $pu = $examen->price;
                }

                $amount_examen_remise = ($examenData['quantity'] * $pu * $examenData['remise']) / 100;
                $amount_remise += $amount_examen_remise;

                $amount += $examenData['quantity'] * $pu;
            }
        }

        if (isset($data['payable_by']) && $data['payable_by']) {
            $data['regulated'] = $data['payable_by'] ? 1 : 0;
        } else {
            $data['regulated'] = $amount == ($amount_remise + $amount_pc) ? 2 : 0;
        }

        $data['amount_pc'] = $amount_pc;
        $data['amount_remise'] = $amount_remise;
        $data['amount'] = $amount;

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
            'assurance' => ['required_if:client,null', 'exists:assureurs,id'],
            'client' => ['required_if:assurance,null', 'exists:clients,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'actualize' => ["boolean"],
        ]);
        $assurance = Assureur::find($request->assurance);
        $client = Client::find($request->client);

        $ref = $client ? $client->ref_cli : $assurance->ref;

        $startDate = Carbon::createFromTimeString($request->input("start_date"));
        $endDate = Carbon::createFromTimeString($request->input("end_date"));
        $fileName = $ref . '-' . $startDate->format('d-m-Y')  . '-' . $endDate->format('d-m-Y') . '.pdf';

        $mediaFacture = Media::where('filename', $fileName)->first();

        if ($mediaFacture) {
            $path = 'storage/' . $mediaFacture->path;
        } else {
            $prestations = Prestation::filterInProgress(
                startDate: $request->input('start_date'),
                endDate: $request->input('end_date'),
                assurance: $request->input('assurance'),
                payableBy: $request->input('client'),
            )
                ->get();

            $centre = Centre::find($request->header('centre'));
            $code = Str::padLeft((FactureAssociate::max('id') ? FactureAssociate::max('id') + 1 : 1), 4, 0) . '/' . Str::upper($centre->reference) . '/' . now()->format('y');
            $media = $centre->medias()->where('name', 'logo')->first();

            $data = [
                'assurance' => $assurance,
                'client' => $client,
                'prestations' => $prestations,
                'code' => $code,
                'centre' => $centre,
                'logo' => $media ? 'storage/' . $media->path . '/' . $media->filename : '',
                "start_date" => $startDate,
                "end_date" => $endDate,
            ];

            $view = $client ? 'pdfs.factures.clients.facture-client-associate' : 'pdfs.factures.assurances.facture-assurance';
            $footer = $client ? 'pdfs.factures.clients.footer' : 'pdfs.factures.assurances.footer';
            $folderPath = $client ? 'storage/facture-clients' : 'storage/facture-assurance';
            $path = $client ? 'storage/facture-clients/' : 'storage/facture-assurance/';

            try {
                save_browser_shot_pdf(
                    view: $view,
                    data: $data,
                    folderPath: $folderPath,
                    path: $path . $fileName,
                    footer: $footer,
                    margins: [15, 10, 15, 10]
                );
            } catch (CouldNotTakeBrowsershot | Throwable $e) {
                Log::error($e->getMessage());

                return \response()->json([
                    'message' => __("Un erreur inattendue est survenu.")
                ], 400);
            }

            $column = $request->input('assurance') ? 'amount_client' : 'amount_pc';
            $totalAmount = DB::table('factures')
                ->join('prestations', 'factures.prestation_id', '=', 'prestations.id')
                ->where('factures.type', 2)
                ->where('factures.state', StateFacture::IN_PROGRESS->value)
                ->where('prestations.centre_id', $request->header('centre'))
                ->when($request->input('assurance'), fn($q) => $q->where('prise_en_charges.assureur_id', $request->input('assurance')))
                ->when($request->input('client'), fn($q) => $q->where('prestations.payable_by', $request->input('client')))
                ->when($request->input('client'), fn($q) => $q->join('clients', 'prestations.payable_by', '=', 'clients.id'))
                ->when($request->input('assurance'), fn($q) => $q->join('prise_en_charges', 'prestations.prise_charge_id', '=', 'prise_en_charges.id'))
                ->selectRaw("SUM(factures.$column) / 100 as total")
                ->value('total');

            if ($request->input('client')) {
                $facture = $client->factures()->create([
                    'start_date' =>  $request->input('start_date'),
                    'end_date' => $request->input('end_date'),
                    'code' => $code,
                    'amount' => $totalAmount,
                    'date' => now(),
                ]);
            } else {
                $facture = $assurance->factures()->create([
                    'start_date' =>  $request->input('start_date'),
                    'end_date' => $request->input('end_date'),
                    'code' => $code,
                    'amount' => $totalAmount,
                    'date' => now(),
                ]);
            }

            $path = $client ? 'facture-clients/' . $fileName : 'facture-assurance/' . $fileName;

            $facture->medias()->create([
                'name' => "FACTURE-ASSOCIATE",
                'disk' => 'public',
                'path' => $path,
                'filename' => $fileName,
                'mimetype' => 'pdf',
                'extension' => 'pdf',
            ]);

            $path = 'storage/' . $path;
        }

        $pdfContent = file_get_contents($path);
        $base64 = base64_encode($pdfContent);

        return response()->json([
            'base64' => $base64,
            'filename' => $fileName
        ]);
    }

    private function createRdv(Prestation $prestation, $consultantId, $clientId, $date)
    {
        $prestation->appointments()->updateOrCreate([
            'client_id' => $prestation->client_id,
            'consultant_id' => $prestation->consultant_id,
            'dateheure_rdv' => $date,
            'details' => "Rendez-vous programmé pour le client " . $prestation->client->nomcomplet_client . " et le consultant " . $prestation->consultant->nomcomplet,
            'nombre_jour_validite' => Setting::where('key', 'rdv_validity_by_day')->first()->value,
            'duration' => Setting::where('key', 'rdv_duration')->first()->value,
        ]);

        //        Todo: Mettre en marche les notifications envoyées
        //        Todo: $consultant = Consultant::find($consultantId);
        //        Todo: $consultant->user()->notify(SendRdvNotification::class);
    }
}
