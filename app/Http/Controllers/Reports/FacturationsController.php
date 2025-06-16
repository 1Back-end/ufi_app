<?php

namespace App\Http\Controllers\Reports;

use App\Enums\StateFacture;
use App\Http\Controllers\Controller;
use App\Models\Centre;
use App\Models\Prestation;
use App\Models\PriseEnCharge;
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
                'factures.regulations',
                'factures.regulations.regulationMethod',
                'client'
            ])
            ->where('centre_id', $request->header('centre'))
            ->where(function (Builder $query) use ($startDate, $endDate) {
                $query->whereHas('factures', function (Builder $query) use ($startDate, $endDate) {
                    $query->where('type', 2)
                        ->whereBetween("date_fact", [$startDate, $endDate])
                        ->where(function (Builder $query) {
                            $query->where('state', StateFacture::PAID)
                                ->orWhere(function (Builder $query) {
                                    $query->where('state', StateFacture::IN_PROGRESS)
                                        ->where(function (Builder $query) {
                                            $query->where(function (Builder $query) {
                                                $query->whereNotNull('prestations.prise_charge_id')
                                                    ->where('amount_client', 0);
                                            })->orWhere(function (Builder $query) {
                                                $query->whereNull('prestations.prise_charge_id')
                                                    ->whereNotNull('prestations.payable_by');
                                            })->orWhereHas('regulations');
                                        });
                                });
                        });
                });
            })
            ->get();

        $amountTotalRegulation = 0;
        $prestations->each(function (Prestation $prestation) use (&$amountTotalRegulation) {
            foreach ($prestation->factures as $facture) {
                if ($facture->type == 2) {
                    foreach ($facture->regulations as $regulation) {
                        if (! $regulation->particular) {
                            $amountTotalRegulation += $regulation->amount;
                        }
                    }
                }
            }
        });

        $amounts = DB::table('factures')
            ->join('prestations', 'factures.prestation_id', '=', 'prestations.id')
            ->whereBetween('date_fact',[$startDate, $endDate])
            ->where('factures.type', 2)
            ->where(function (QueryBuilder $query) {
                $query->where('factures.state', StateFacture::PAID->value)
                    ->orWhere(function (QueryBuilder $query) {
                        $query->where('factures.state', StateFacture::IN_PROGRESS->value)
                            ->where(function (QueryBuilder $query) {
                                $query->where(function (QueryBuilder $query) {
                                    $query->whereNotNull('prestations.prise_charge_id')
                                        ->where('amount_client', 0);
                                })->orWhere(function (QueryBuilder $query) {
                                    $query->whereNull('prestations.prise_charge_id')
                                        ->whereNotNull('prestations.payable_by');
                                })->orWhereExists(function (QueryBuilder $subQuery) {
                                    $subQuery->select(DB::raw(1))
                                        ->from('regulations')
                                        ->whereColumn('regulations.facture_id', 'factures.id');
                                });
                            });
                    });
            })
            ->where('prestations.centre_id', $request->header('centre'))
            ->selectRaw("SUM(factures.amount) / 100 as total")
            ->selectRaw("SUM(factures.amount_pc) / 100 as total_pc")
            ->selectRaw("SUM(factures.amount_client) / 100 as total_client")
            ->selectRaw("SUM(factures.amount_remise) / 100 as total_remise")
            ->get();

        $centre = Centre::find($request->header('centre'));
        $media = $centre->medias()->where('name', 'logo')->first();

        $data = [
            'prestations' => $prestations,
            'logo' => $media ? 'storage/' . $media->path . '/' . $media->filename : '',
            'amounts' => $amounts,
            'amountTotalRegulation' => $amountTotalRegulation,
            'centre' => $centre,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        $folderPath = "storage/daily-caisse";
        $fileName = "Etat_des_règlements_clients_" . $centre->reference . "_Perioid_" . $startDate->format("d_m_Y") . "_au_" . $endDate->format("d_m_Y") . '.pdf';
        $path = "$folderPath/$fileName";
        $footer = 'pdfs.reports.factures.footer';

        DB::beginTransaction();
        try {
            save_browser_shot_pdf(
                view: 'pdfs.reports.factures.daily',
                data: $data,
                folderPath: $folderPath,
                path: $path,
                footer: $footer,
                margins: [15, 10, 15, 10]
            );

            $centre->medias()->create([
                'name' => "DAILY-FACTURE",
                'disk' => 'public',
                'path' => $path,
                'filename' => $fileName,
                'mimetype' => 'pdf',
                'extension' => 'pdf',
            ]);
        }
        catch (CouldNotTakeBrowsershot|Throwable $e) {
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
            },
            'prestations.factures' => function ($query) {
                $query->where('factures.type', 2);
            },
            'prestations.actes',
            'prestations.soins',
            'prestations.consultations',
            'prestations.hospitalisations',
            'prestations.products',
            'client:id,nom_cli,prenom_cli,nomcomplet_client,ref_cli,date_naiss_cli',
            'prestations.priseCharge:id,assureur_id,taux_pc',
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
                                    ->where(function ( $query) {
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
                margins: [15, 10, 15, 10]
            );

            $centre->medias()->create([
                'name' => "PRISE-EN-CHARGE",
                'disk' => 'public',
                'path' => $path,
                'filename' => $fileName,
                'mimetype' => 'pdf',
                'extension' => 'pdf',
            ]);
        }
        catch (CouldNotTakeBrowsershot|Throwable $e) {
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
}
