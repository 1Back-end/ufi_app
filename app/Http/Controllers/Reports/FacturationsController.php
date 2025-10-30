<?php

namespace App\Http\Controllers\Reports;

use App\Enums\StateFacture;
use App\Enums\StatusRegulation;
use App\Enums\TypePrestation;
use App\Http\Controllers\Controller;
use App\Models\Acte;
use App\Models\Assureur;
use App\Models\Centre;
use App\Models\Consultation;
use App\Models\Facture;
use App\Models\OpsTblHospitalisation;
use App\Models\Prestation;
use App\Models\PriseEnCharge;
use App\Models\Product;
use App\Models\Regulation;
use App\Models\Soins;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Exceptions\CouldNotTakeBrowsershot;
use Throwable;

class FacturationsController extends Controller
{

    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @permission Reports\FacturationsController::reportCaisse
     * @permission_desc Télécharger le rapport de la caisse sur une période.
     * @throws Throwable
     */
    public function reportCaisse(Request $request): JsonResponse
    {
        $startDate = Carbon::parse($request->input('start'))->startOfDay() ?? now()->startOfDay();
        $endDate = Carbon::parse($request->input('end'))->endOfDay() ?? now()->endOfDay();


        $prestations = Prestation::query()
            ->with([
                'factures' => fn($q) => $q->where('factures.type', 2),
                'centre',
                'factures.regulations' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('date', [$startDate, $endDate])
                        ->where('particular', false)
                        ->where('state', StatusRegulation::ACTIVE->value);
                },
                'factures.regulations.regulationMethod',
                'client',
                'priseCharge',
                'payableBy'
            ])
            ->where('centre_id', $request->header('centre'))
            ->when($request->input('type'), fn($q) => $q->where('prestations.type', $request->input('type')))
            ->when($request->input('client_id'), fn($q) => $q->where('prestations.client_id', $request->input('client_id')))
            ->when($request->input('consultant_id'), fn($q) => $q->where('prestations.consultant_id', $request->input('consultant_id')))
            ->when($request->input('payment_mode') && $request->input('payment_mode') == "associate-client", fn($q) => $q->has('payableBy'))
            ->when($request->input('payment_mode') && $request->input('payment_mode') == "pris-en-charge", fn($q) => $q->has('priseCharge'))
            ->when($request->input('payment_mode') && $request->input('payment_mode') == "comptant", fn($q) => $q->whereNull('prestations.prise_charge_id')
                ->whereNull('prestations.payable_by'))
            ->when($request->input('payable_by'), fn($q) => $q->where('prestations.payable_by', $request->input('payable_by')))
            ->when($request->input('prise_charge_id'), fn($q) => $q->where('prestations.prise_charge_id', $request->input('prise_charge_id')))
            ->when($request->input('regulation_method'), fn($q) => $q->whereHas('factures', function ($query) use ($request) {
                $query->whereHas('regulations', function ($query) use ($request) {
                    $query->where('regulation_method_id', $request->input('regulation_method'));
                });
            }))
            ->when(! $request->input('rapprochement'), function ($query) use ($request, $startDate, $endDate) {
                $query->where($this->getClosure($startDate, $endDate, $request));
            }, function ($query) use ($startDate, $endDate) {
                $query->whereHas('factures', function (Builder $query) use ($startDate, $endDate) {
                    $query->where('type', 2)
                        ->whereBetween("date_fact", [$startDate, $endDate]);
                });
            })
            ->orWhereHas('factures', function ($query) use ($startDate, $endDate) {
                $query->whereHas('regulations', function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('date', [$startDate, $endDate])
                        ->where('particular', false)
                        ->where('state', StatusRegulation::ACTIVE->value);
                });
            })
            ->get();

        $amounts = $prestations->reduce(function ($carry, Prestation $prestation) {
            $facture = $prestation->factures->first();
            $carry['total'] += $prestation->factures->first()->amount;
            $carry['amount_rest'] += $prestation->factures->first()->amount_rest;
            $carry['total_client'] += $prestation->factures->first()->amount_client;
            $carry['total_remise'] += $prestation->factures->first()->amount_remise;
            $carry['amount_total_regulation'] += $prestation->factures->first()->regulations_total_except_particular;

            $regulations = $facture->regulations()->where('regulations.particular', false)->get();
            if ($regulations->isNotEmpty()) {
                $regulations->each(function (Regulation $regulation) use (&$carry) {
                    if (! isset($carry['amount_per_method'][$regulation->regulationMethod->name])) {
                        $carry['amount_per_method'][$regulation->regulationMethod->name] = 0;
                    }
                    $carry['amount_per_method'][$regulation->regulationMethod->name] += $regulation->amount;
                });
            }
            return $carry;
        }, ['total' => 0, 'amount_rest' => 0, 'total_client' => 0, 'total_remise' => 0, 'amount_total_regulation' => 0, 'amount_per_method' => []]);

        $centre = Centre::find($request->header('centre'));
        $media = $centre->medias()->where('name', 'logo')->first();

        $data = [
            'prestations' => $prestations,
            'logo' => $media ? 'storage/' . $media->path . '/' . $media->filename : '',
            'amounts' => $amounts,
            'centre' => $centre,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'request' => $request->all(),
            'rapprochement' => $request->input('rapprochement')
        ];

        $folderPath = "storage/daily-caisse";
        $fileName = "Etat_des_règlements_clients_" . $centre->reference . "_Period_" . $startDate->format("d_m_Y") . "_au_" . $endDate->format("d_m_Y") . '.pdf';
        $path = "$folderPath/$fileName";
        $footer = 'pdfs.reports.factures.footer';

        DB::beginTransaction();
        try {
            save_browser_shot_pdf(
                view: 'pdfs.reports.factures.daily',
                data: $data,
                folderPath: $folderPath,
                path: $path,
                direction: $request->input('rapprochement') ? 'landscape' : '',
                footer: $footer,
                margins: [5, 8, 10, 8]
            );

            $centre->medias()->create([
                'name' => "DAILY-FACTURE",
                'disk' => 'public',
                'path' => $path,
                'filename' => $fileName,
                'mimetype' => 'pdf',
                'extension' => 'pdf',
            ]);
        } catch (CouldNotTakeBrowsershot | Throwable $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return \response()->json([
                'message' => __("Un erreur inattendue est survenu.")
            ], 400);
        }
        DB::commit();

        $pdfContent = file_get_contents($path);
        $base64 = base64_encode($pdfContent);

        return response()->json([
            'base64' => $base64,
            'filename' => $fileName
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @permission Reports\FacturationsController::priseCharge
     * @permission_desc Télécharger le rapport de prise de charge sur une période.
     * @throws Throwable
     */
    public function priseCharge(Request $request): JsonResponse
    {
        $startDate = Carbon::parse($request->input('start'))->startOfDay() ?? now()->startOfDay();
        $endDate = Carbon::parse($request->input('end'))->endOfDay() ?? now()->endOfDay();

        $priseCharges = PriseEnCharge::with([
            'assureur',
            'prestations' => function ($query) use ($request, $startDate, $endDate) {
                $query->where('centre_id', $request->header('centre'))
                    ->whereHas('factures', function ($query) use ($request, $startDate, $endDate) {
                        $query->where('factures.type', 2)
                            ->when($startDate && $endDate, function ($query) use ($request, $startDate, $endDate) {
                                $query->whereBetween('factures.date_fact', [$startDate, $endDate]);
                            })
                            ->where(function ($query) {
                                $query->where('factures.state', StateFacture::PAID)
                                    ->orWhere(function (Builder $query) {
                                        $query->where('factures.state', StateFacture::IN_PROGRESS)
                                            ->where(function (Builder $query) {
                                                $query->where(function (Builder $query) {
                                                    $query->whereNotNull('prestations.prise_charge_id')
                                                        ->where('factures.amount_pc', '>', 0);
                                                })->orWhere(function (Builder $query) {
                                                    $query->whereNull('prestations.prise_charge_id')
                                                        ->whereNotNull('prestations.payable_by');
                                                })->orWhereHas('regulations');
                                            });
                                    });
                            });
                    });
            },
            'prestations.factures' => function ($query) {
                $query->where('factures.type', 2);
            },
            'prestations.actes',
            'prestations.soins',
            'prestations.consultations',
            'prestations.hospitalisations',
            'prestations.products',
            'client',
            'prestations.priseCharge',
        ])
            ->whereHas('prestations', function ($query) use ($request, $startDate, $endDate) {
                $query->whereHas('factures', function ($query) use ($request, $startDate, $endDate) {
                    $query->where('factures.type', 2)
                        ->when($startDate && $endDate, function ($query) use ($request, $startDate, $endDate) {
                            $query->whereBetween('factures.date_fact', [$startDate, $endDate]);
                        })
                        ->where(function ($query) {
                            $query->where('factures.state', StateFacture::PAID)
                                ->orWhere(function (Builder $query) {
                                    $query->where('factures.state', StateFacture::IN_PROGRESS)
                                        ->where(function (Builder $query) {
                                            $query->where(function (Builder $query) {
                                                $query->whereNotNull('prestations.prise_charge_id')
                                                    ->where('factures.amount_pc', '>', 0);
                                            })->orWhere(function (Builder $query) {
                                                $query->whereNull('prestations.prise_charge_id')
                                                    ->whereNotNull('prestations.payable_by');
                                            })->orWhereHas('regulations');
                                        });
                                });
                        });
                });
            })
            ->select('prise_en_charges.*')
            ->selectSub(function ($query) use ($request, $startDate, $endDate) {
                $query->from('factures')
                    ->join('prestations', 'prestations.id', '=', 'factures.prestation_id')
                    ->where('factures.type', 2)
                    ->where(function ($query) {
                        $query->where('factures.state', StateFacture::PAID)
                            ->orWhere(function ($query) {
                                $query->where('factures.state', StateFacture::IN_PROGRESS)
                                    ->where(function ($query) {
                                        $query->where(function ($query) {
                                            $query->whereNotNull('prestations.prise_charge_id')
                                                ->where('factures.amount_pc', '>', 0);
                                        })->orWhere(function ($query) {
                                            $query->whereNull('prestations.prise_charge_id')
                                                ->whereNotNull('prestations.payable_by');
                                        })->orWhereExists(function ($q) {
                                            $q->select(DB::raw(1))
                                                ->from('regulations')
                                                ->whereColumn('regulations.facture_id', 'factures.id');
                                        });
                                    });
                            });
                    })
                    ->whereBetween('factures.date_fact', [$startDate, $endDate])
                    ->selectRaw('SUM(factures.amount_pc) / 100');
            }, 'total_amount')
            ->get();

        $centre = Centre::find($request->header('centre'));
        $media = $centre->medias()->where('name', 'logo')->first();

        $data = [
            'priseCharges' => $priseCharges,
            'logo' => $media ? 'storage/' . $media->path . '/' . $media->filename : '',
            'centre' => $centre,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        $folderPath = "storage/prise-en-charge";
        $fileName = "RELEVE_DES_PRISES_DE_CHARGE_" . $centre->reference . "_Perioid_" . $startDate->format("d_m_Y") . "_au_" . $endDate->format("d_m_Y") . '.pdf';
        $path = "$folderPath/$fileName";
        $footer = 'pdfs.reports.factures.footer';

        DB::beginTransaction();
        try {
            save_browser_shot_pdf(
                view: 'pdfs.reports.factures.priseencharge',
                data: $data,
                folderPath: $folderPath,
                path: $path,
                footer: $footer,
                margins: [5, 8, 10, 8]
            );

            $centre->medias()->create([
                'name' => "PRISE-EN-CHARGE",
                'disk' => 'public',
                'path' => $path,
                'filename' => $fileName,
                'mimetype' => 'pdf',
                'extension' => 'pdf',
            ]);
        } catch (CouldNotTakeBrowsershot | Throwable $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return \response()->json([
                'message' => __("Un erreur inattendue est survenu.")
            ], 400);
        }
        DB::commit();

        $pdfContent = file_get_contents($path);
        $base64 = base64_encode($pdfContent);

        return response()->json([
            'base64' => $base64,
            'filename' => $fileName
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @permission Reports\FacturationsController::examenFactures
     * @permission_desc Télécharger la liste des examens des facturés
     * @throws Throwable
     */
    public function examenFactures(Request $request): JsonResponse
    {
        $startDate = Carbon::parse($request->input('start'))->startOfDay() ?? now()->startOfDay();
        $endDate = Carbon::parse($request->input('end'))->endOfDay() ?? now()->endOfDay();

        $centre = Centre::find($request->header('centre'));
        $media = $centre->medias()->where('name', 'logo')->first();

        $prestationsPrisCharges = collect();
        $prestationsNonPrisCharges = collect();
        $amountPrisCharges = 0;
        $amountNonPrisCharges = 0;
        $prestations = Prestation::query()
            ->with([
                'factures' => fn($q) => $q->where('factures.type', 2),
                'centre',
            ])

            ->where('centre_id', $request->header('centre'))
            ->when($request->input('type'), fn($q) => $q->where('prestations.type', $request->input('type')))
            ->when($request->input('client_id'), fn($q) => $q->where('prestations.client_id', $request->input('client_id')))
            ->when($request->input('consultant_id'), fn($q) => $q->where('prestations.consultant_id', $request->input('consultant_id')))

            ->when($request->input('payment_mode') && $request->input('payment_mode') == "associate-client", fn($q) => $q->has('payableBy'))
            ->when($request->input('payment_mode') && $request->input('payment_mode') == "pris-en-charge", fn($q) => $q->has('priseCharge'))
            ->when($request->input('payment_mode') && $request->input('payment_mode') == "comptant", fn($q) => $q->whereNull('prestations.prise_charge_id')->whereNull('prestations.payable_by'))

            ->when($request->input('payable_by'), fn($q) => $q->where('prestations.payable_by', $request->input('payable_by')))
            ->when($request->input('prise_charge_id'), fn($q) => $q->where('prestations.prise_charge_id', $request->input('prise_charge_id')))

            ->where($this->getClosure($startDate, $endDate, $request))

            ->get()
            ->each(function (Prestation $prestation) use ($prestationsPrisCharges, $prestationsNonPrisCharges, &$amountPrisCharges, &$amountNonPrisCharges) {
                if ($prestation->priseCharge) {
                    $prestationsPrisCharges->push($prestation);
                } else {
                    $prestationsNonPrisCharges->push($prestation);
                }

                $prestation->actes->each(function (Acte $acte) use ($prestation, &$amountPrisCharges, &$amountNonPrisCharges) {
                    if ($prestation->priseCharge) {
                        $amountPrisCharges += $acte->pivot->b * $acte->pivot->k_modulateur;
                    } else {
                        $amountNonPrisCharges += $acte->pivot->b * $acte->pivot->k_modulateur;
                    }
                });

                $prestation->soins->each(function (Soins $soins) use ($prestation, &$amountPrisCharges, &$amountNonPrisCharges) {
                    if ($prestation->priseCharge) {
                        $amountPrisCharges += $soins->pivot->pu;
                    } else {
                        $amountNonPrisCharges += $soins->pivot->pu;
                    }
                });

                $prestation->consultations->each(function (Consultation $consultation) use ($prestation, &$amountPrisCharges, &$amountNonPrisCharges) {
                    if ($prestation->priseCharge) {
                        $amountPrisCharges += $consultation->pivot->pu;
                    } else {
                        $amountNonPrisCharges += $consultation->pivot->pu;
                    }
                });

                $prestation->hospitalisations->each(function (OpsTblHospitalisation $hospitalisation) use ($prestation, &$amountPrisCharges, &$amountNonPrisCharges) {
                    if ($prestation->priseCharge) {
                        $amountPrisCharges += $hospitalisation->pivot->pu;
                    } else {
                        $amountNonPrisCharges += $hospitalisation->pivot->pu;
                    }
                });

                $prestation->products->each(function (Product $product) use ($prestation, &$amountPrisCharges, &$amountNonPrisCharges) {
                    $amountNonPrisCharges += $product->pivot->pu;
                });
            });

        $data = [
            'prestationsPrisCharges' => $prestationsPrisCharges,
            'prestationsNonPrisCharges' => $prestationsNonPrisCharges,
            'amountPrisCharges' => $amountPrisCharges,
            'amountNonPrisCharges' => $amountNonPrisCharges,
            'logo' => $media ? 'storage/' . $media->path . '/' . $media->filename : '',
            'centre' => $centre,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        $folderPath = "storage/examen-factures";
        $fileName = "LISTE_DES_EXAMENS_DES_FACTURES_" . $centre->reference . "_PERIOD_" . $startDate->format("d_m_Y") . "_AU_" . $endDate->format("d_m_Y") . '.pdf';
        $path = "$folderPath/$fileName";
        $footer = 'pdfs.reports.factures.footer';

        DB::beginTransaction();
        try {
            save_browser_shot_pdf(
                view: 'pdfs.reports.factures.examenfactures',
                data: $data,
                folderPath: $folderPath,
                path: $path,
                footer: $footer,
                margins: [5, 8, 10, 8]
            );

            $centre->medias()->create([
                'name' => "EXAMEN-FACTURES",
                'disk' => 'public',
                'path' => $path,
                'filename' => $fileName,
                'mimetype' => 'pdf',
                'extension' => 'pdf',
            ]);
        } catch (CouldNotTakeBrowsershot | Throwable $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return \response()->json([
                'message' => __("Un erreur inattendue est survenu.")
            ], 400);
        }
        DB::commit();

        $pdfContent = file_get_contents($path);
        $base64 = base64_encode($pdfContent);

        return response()->json([
            'base64' => $base64,
            'filename' => $fileName
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @permission Reports\FacturationsController::priseChargeInProgress
     * @permission_desc Télécharger le rapport de prise de charge en cours.
     * @throws Throwable
     */
    public function priseChargeInProgress(Request $request): JsonResponse
    {
        $assurances = Assureur::with([
            'priseEnCharges' => function ($query) use ($request) {
                $query->when($request->input('client_id'), function ($query) use ($request) {
                    $query->whereHas('client', function ($query) use ($request) {
                        $query->where('clients.id', $request->input('client_id'));
                    });
                });
            },
            'priseEnCharges.client' => function ($query) use ($request) {
                $query->when($request->input('client_id'), function ($query) use ($request) {
                    $query->where('clients.id', $request->input('client_id'));
                });
            },
        ])
            ->whereHas('priseEnCharges', function ($query) {
                $query->where('is_deleted', false)
                    ->whereDate('date_fin', '>=', now())
                    ->where('used', false);;
            })
            ->when($request->input('client_id'), function ($query) use ($request) {
                $query->whereHas('priseEnCharges', function ($query) use ($request) {
                    $query->where('client_id', $request->input('client_id'));
                });
            })
            ->orderBy('nom')
            ->get();


        $centre = Centre::find($request->header('centre'));
        $media = $centre->medias()->where('name', 'logo')->first();

        $data = [
            'assurances' => $assurances,
            'logo' => $media ? 'storage/' . $media->path . '/' . $media->filename : '',
            'centre' => $centre,
        ];

        $folderPath = "storage/prise-charge-in-progress";
        $fileName = "PRISES_DE_CHARGE_EN_COURS_" . $centre->reference . "_PERIOD_" . now()->format("d_m_Y") . '.pdf';
        $path = "$folderPath/$fileName";
        $footer = 'pdfs.reports.factures.footer';

        DB::beginTransaction();
        try {
            save_browser_shot_pdf(
                view: 'pdfs.reports.prisecharges.priseenchargeinprogress',
                data: $data,
                folderPath: $folderPath,
                path: $path,
                footer: $footer,
                margins: [5, 8, 10, 8]
            );

            $centre->medias()->create([
                'name' => "PRISE-EN-CHARGE-EN-COURS",
                'disk' => 'public',
                'path' => $path,
                'filename' => $fileName,
                'mimetype' => 'pdf',
                'extension' => 'pdf',
            ]);
        } catch (CouldNotTakeBrowsershot | Throwable $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return \response()->json([
                'message' => __("Un erreur inattendue est survenu.")
            ], 400);
        }
        DB::commit();

        $pdfContent = file_get_contents($path);
        $base64 = base64_encode($pdfContent);

        return response()->json([
            'base64' => $base64,
            'filename' => $fileName
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @permission Reports\FacturationsController::stateConsultantPrescription
     * @permission_desc Télécharger le rapport de l'état des prescriptions des consultants.
     * @throws Throwable
     */
    public function stateConsultantPrescription(Request $request): JsonResponse
    {
        $startDate = Carbon::parse($request->input('start'))->startOfDay() ?? now()->startOfDay();
        $endDate = Carbon::parse($request->input('end'))->endOfDay() ?? now()->endOfDay();

        $centre = Centre::find($request->header('centre'));
        $media = $centre->medias()->where('name', 'logo')->first();

        $consultants = Prestation::query()
            ->with([
                'factures' => fn($q) => $q->where('factures.type', 2),
                'centre',
                'consultant',
                'priseCharge',
            ])

            ->where('centre_id', $request->header('centre'))
            ->when($request->input('type'), fn($q) => $q->where('prestations.type', $request->input('type')))
            ->when($request->input('client_id'), fn($q) => $q->where('prestations.client_id', $request->input('client_id')))
            ->when($request->input('consultant_id'), fn($q) => $q->where('prestations.consultant_id', $request->input('consultant_id')))

            ->when($request->input('payment_mode') && $request->input('payment_mode') == "associate-client", fn($q) => $q->has('payableBy'))
            ->when($request->input('payment_mode') && $request->input('payment_mode') == "pris-en-charge", fn($q) => $q->has('priseCharge'))
            ->when($request->input('payment_mode') && $request->input('payment_mode') == "comptant", fn($q) => $q->whereNull('prestations.prise_charge_id')->whereNull('prestations.payable_by'))

            ->when($request->input('payable_by'), fn($q) => $q->where('prestations.payable_by', $request->input('payable_by')))
            ->when($request->input('prise_charge_id'), fn($q) => $q->where('prestations.prise_charge_id', $request->input('prise_charge_id')))

            ->where($this->getClosure($startDate, $endDate, $request))
            ->get()
            ->groupBy('consultant_id');


        $data = [
            'consultants' => $consultants,
            'logo' => $media ? 'storage/' . $media->path . '/' . $media->filename : '',
            'centre' => $centre,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        $folderPath = "storage/etat-prescriptions-consultants";
        $fileName = "ETAT_PRESCRIPTIONS_CONSULTANTS_" . $centre->reference . "_PERIOD_" . $startDate->format("d_m_Y") . "_AU_" . $endDate->format("d_m_Y") . '.pdf';
        $path = "$folderPath/$fileName";
        $footer = 'pdfs.layouts.footer';

        DB::beginTransaction();
        try {
            save_browser_shot_pdf(
                view: 'pdfs.reports.factures.state_consultant_prescription',
                data: $data,
                folderPath: $folderPath,
                path: $path,
                footer: $footer,
                margins: [5, 8, 10, 8]
            );

            $centre->medias()->create([
                'name' => "ETAT-PRESCRIPTIONS-CONSULTANTS",
                'disk' => 'public',
                'path' => $path,
                'filename' => $fileName,
                'mimetype' => 'pdf',
                'extension' => 'pdf',
            ]);
        } catch (CouldNotTakeBrowsershot | Throwable $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return \response()->json([
                'message' => __("Un erreur inattendue est survenu.")
            ], 400);
        }
        DB::commit();

        $pdfContent = file_get_contents($path);
        $base64 = base64_encode($pdfContent);

        return response()->json([
            'base64' => $base64,
            'filename' => $fileName
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @permission Reports\FacturationsController::facturesNonSolde
     * @permission_desc Telecharger le rapport des factures non soldees.
     * @throws Throwable
     */
    public function facturesNonSolde(Request $request)
    {
        $startDate = Carbon::parse($request->input('start'))->startOfDay() ?? now()->startOfDay();
        $endDate = Carbon::parse($request->input('end'))->endOfDay() ?? now()->endOfDay();

        $centre = Centre::find($request->header('centre'));
        $media = $centre->medias()->where('name', 'logo')->first();

        $dateFactures = Facture::query()
            ->with([
                'prestation.client',
                'regulations' => fn($q) => $q->where('particular', false),
            ])
            ->where('type', 2)
            ->where('centre_id', $centre->id)
            ->whereBetween('date_fact', [$startDate, $endDate])
            ->where('state', StateFacture::IN_PROGRESS->value)
            ->get()
            ->groupBy(function (Facture $facture) {
                return $facture->date_fact->format('d-m-Y');
            });

        $data = [
            'dateFactures' => $dateFactures,
            'logo' => $media ? 'storage/' . $media->path . '/' . $media->filename : '',
            'centre' => $centre,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        $folderPath = "storage/factures-non-solde";
        $fileName = "FACTURES_NON_SOLDE_" . $centre->reference . "_PERIOD_" . $startDate->format("d_m_Y") . "_AU_" . $endDate->format("d_m_Y") . '.pdf';
        $path = "$folderPath/$fileName";
        $footer = 'pdfs.layouts.footer';

        DB::beginTransaction();
        try {
            save_browser_shot_pdf(
                view: 'pdfs.reports.factures.factures_non_solde',
                data: $data,
                folderPath: $folderPath,
                path: $path,
                footer: $footer,
                margins: [5, 8, 10, 8]
            );

            $centre->medias()->create([
                'name' => "FACTURES-NON-SOLDE",
                'disk' => 'public',
                'path' => $path,
                'filename' => $fileName,
                'mimetype' => 'pdf',
                'extension' => 'pdf',
            ]);
        } catch (CouldNotTakeBrowsershot | Throwable $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return \response()->json([
                'message' => __("Un erreur inattendue est survenu.")
            ], 400);
        }
        DB::commit();

        $pdfContent = file_get_contents($path);
        $base64 = base64_encode($pdfContent);

        return response()->json([
            'base64' => $base64,
            'filename' => $fileName
        ]);
    }

    /**
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param Request $request
     * @return \Closure
     */
    public function getClosure(Carbon $startDate, Carbon $endDate, Request $request): \Closure
    {
        return function (Builder $query) use ($startDate, $endDate, $request) {
            $query->whereHas('factures', function (Builder $query) use ($startDate, $endDate, $request) {
                $query->where('type', 2)
                    ->whereBetween("date_fact", [$startDate, $endDate])
                    ->where(function (Builder $query) use ($request) {
                        $query->where('state', StateFacture::PAID)
                            ->orWhere(function (Builder $query) use ($request) {
                                $query->where('state', StateFacture::IN_PROGRESS)
                                    ->where(function (Builder $query) use ($request) {
                                        $query->where(function (Builder $query) use ($request) {
                                            $query->whereNotNull('prestations.prise_charge_id')
                                                ->where('amount_client', 0);
                                        })->orWhere(function (Builder $query) use ($request) {
                                            $query->whereNull('prestations.prise_charge_id')
                                                ->whereNotNull('prestations.payable_by');
                                        })->orWhereHas('regulations');
                                    });
                            });
                    });
            });
        };
    }
}
