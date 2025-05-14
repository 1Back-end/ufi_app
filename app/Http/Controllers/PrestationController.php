<?php

namespace App\Http\Controllers;

use App\Enums\StateFacture;
use App\Enums\TypePrestation;
use App\Http\Requests\PrestationRequest;
use App\Models\Acte;
use App\Models\Assureur;
use App\Models\Client;
use App\Models\Facture;
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
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PHPUnit\Framework\Exception;
use Symfony\Component\HttpFoundation\Response;

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
            'centre',
            'factures',
            'factures.regulations',
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
        })->when($request->input('regulated'), function (Builder $query) use ($request) {
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

    public function getFacturesInProgress(Request $request)
    {
        $request->validate([
            'assurance' => ['', 'exists:assureurs,id'],
            'payable_by' => ['', 'exists:clients,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
        ]);

        $assureurs = [];
        $clients = [];

        if ($request->input('assurance')) {
            $assureurs = PriseEnCharge::with([
                'assureur:id,nom',
                'prestations' => function ($query) use ($request) {
                    $query->whereHas('factures', function ($query) use ($request) {
                        $query->where('factures.type', 2)
                            ->where('factures.state', StateFacture::IN_PROGRESS->value)
                            ->when($request->input('start_date') && $request->input('end_date'), function ($query) use ($request) {
                                $query->whereBetween('factures.date_fact', [$request->input('start_date'), $request->input('end_date')]);
                            });
                    });
                },
                'prestations.factures' => function ($query) {
                    $query->where('factures.type', 2);
                },
                'prestations.actes',
                'prestations.soins',
                'client:id,nom_cli,prenom_cli,nomcomplet_client,ref_cli,date_naiss_cli',
                'prestations.priseCharge:id,assureur_id,taux_pc',
            ])
                ->when($request->input('assurance'), function ($query) use ($request) {
                    $query->whereHas('assureur', function ($query) use ($request) {
                        $query->where('assureurs.id', $request->input('assurance'));
                    });
                })
                ->whereHas('prestations', function ($query) use ($request) {
                    $query->whereHas('factures', function ($query) use ($request) {
                        $query->where('factures.type', 2)
                            ->where('factures.state', StateFacture::IN_PROGRESS->value)
                            ->whereBetween('factures.date_fact', [$request->input('start_date'), $request->input('end_date')]);
                    });
                })
                ->select('prise_en_charges.*') // important
                ->selectSub(function ($query) use ($request) {
                    $query->from('factures')
                        ->join('prestations', 'prestations.id', '=', 'factures.prestation_id')
                        ->whereColumn('prestations.prise_charge_id', 'prise_en_charges.id')
                        ->where('factures.type', 2)
                        ->where('factures.state', StateFacture::IN_PROGRESS->value)
                        ->whereBetween('factures.date_fact', [$request->input('start_date'), $request->input('end_date')])
                        ->selectRaw('SUM(factures.amount_pc) / 100');
                }, 'total_amount')
                ->get();

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

        return response()->json([
            'assureurs' => $assureurs,
            'clients' => $clients,
            'regulation_methods' => RegulationMethod::get()->toArray(),
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
                    $prestation->actes()->attach($item['id'], [
                        'remise' => $item['remise'],
                        'quantity' => $item['quantity'],
                        'date_rdv' => $item['date_rdv'],
                        'date_rdv_end' => Carbon::createFromTimeString($item['date_rdv'])->addHours($prestationDuration)
                    ]);
                }
                break;
            case TypePrestation::SOINS->value:
                if ($update) {
                    $prestation->soins()->detach();
                }

                foreach ($request->post('soins') as $item) {
                    $prestation->soins()->attach($item['id'], [
                        'remise' => $item['remise'],
                        'nbr_days' => $item['nbr_days'],
                        'type_salle' => $item['type_salle'],
                        'honoraire' => $item['honoraire'],
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
     */
    public function getDataForPriseEnCharge(PrestationRequest $request, mixed &$data): mixed
    {
        if ($request->input('prise_charge_id')) {
            $amount = 0;
            $amount_pc = 0;
            $amount_remise = 0;
            $priseCharge = PriseEnCharge::find($request->input('prise_charge_id'));

            if ($priseCharge->usage_unique == "Oui") {
                $priseCharge->update(['used' => true]);
            }

            foreach ($request->input('actes') as $acteData) {
                $acte = Acte::find($acteData['id']);
                $pu = $acte->b * $acte->k_modulateur;
                $amount_acte_pc = ($acteData['quantity'] * $pu * $priseCharge->taux_pc) / 100;
                $amount_pc += $amount_acte_pc;

                $amount_acte_remise = ($acteData['quantity'] * $pu * $acteData['remise']) / 100;
                $amount_remise += $amount_acte_remise;

                $amount += $acteData['quantity'] * $pu;
            }

            foreach ($request->input('soins') as $soinData) {
                $soin = Soins::find($soinData['id']);
                $pu = $soin->pu;
                $amount_acte_pc = ($soinData['nbr_days'] * $pu * $priseCharge->taux_pc) / 100;
                $amount_pc += $amount_acte_pc;

                $amount_acte_remise = ($soinData['nbr_days'] * $pu * $soinData['remise']) / 100;
                $amount_remise += $amount_acte_remise;

                $amount += $soinData['nbr_days'] * $pu;
            }
            $data['regulated'] = $amount <= ($amount_remise + $amount_pc) ? 1 : 0;
        } else {
            $data['regulated'] = $data['payable_by'] ? 1 : 0;
        }
        return $data;
    }
}
