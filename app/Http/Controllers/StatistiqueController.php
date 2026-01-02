<?php

namespace App\Http\Controllers;

use App\Enums\StateFacture;
use App\Enums\StockAdjustmentAction;
use App\Enums\TypeClient;
use App\Enums\TypePrestation;
use App\Models\Centre;
use App\Models\Client;
use App\Models\Consultant;
use App\Models\Examen;
use App\Models\Facture;
use App\Models\Prestation;
use App\Models\Proforma;
use App\Models\Regulation;
use App\Models\RendezVous;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @permission_category Gestion des statistiques
 */

class StatistiqueController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission StatistiqueController::clientsJourParType
     * @permission_desc Statistiques des types de clients par jour
     */
    public function clientsJourParType()
    {
        $today     = Carbon::today();
        $yesterday = Carbon::yesterday();
        $response  = [];

        foreach (TypeClient::cases() as $type) {
            $typeValue = $type->value;

            // Clients facturés aujourd'hui avec régulation validée
            $nouveaux = Prestation::whereHas('factures', function ($q) use ($today) {
                $q->whereDate('date_fact', $today)
                    ->whereHas('regulations', function ($r) use ($today) {
                        $r->whereDate('created_at', $today)
                            ->where('state', 1);
                    });
            })
                ->whereHas('client', function ($q) use ($typeValue) {
                    $q->where('type_cli', $typeValue);
                })
                ->with('client')
                ->get()
                ->pluck('client.id')
                ->unique()
                ->count();

            // Clients facturés hier avec régulation validée
            $anciens = Prestation::whereHas('factures', function ($q) use ($yesterday) {
                $q->whereDate('date_fact', $yesterday)
                    ->whereHas('regulations', function ($r) use ($yesterday) {
                        $r->whereDate('created_at', $yesterday)
                            ->where('state', 1);
                    });
            })
                ->whereHas('client', function ($q) use ($typeValue) {
                    $q->where('type_cli', $typeValue);
                })
                ->with('client')
                ->get()
                ->pluck('client.id')
                ->unique()
                ->count();

            $response[$typeValue] = [
                'nouveaux' => $nouveaux,
                'anciens'  => $anciens,
            ];
        }

        return response()->json($response);
    }





    /**
     * Display a listing of the resource.
     * @permission StatistiqueController::statistiquesAujourdHui
     * @permission_desc Statistiques de Rendez-vous par état
     */
    public function statistiquesAujourdHui()
    {
        try {
            $aujourdhui = now()->startOfDay();  // date à 00h00 aujourd'hui
            $finJournee = now()->endOfDay();    // date à 23h59:59 aujourd'hui

            // Total des rendez-vous aujourd'hui
            $totalParJour = RendezVous::where('is_deleted', false)
                ->whereBetween('created_at', [$aujourdhui, $finJournee])
                ->count();

            // Rendez-vous par état aujourd'hui
            $parEtat = RendezVous::select('etat', DB::raw('count(*) as total'))
                ->where('is_deleted', false)
                ->whereBetween('created_at', [$aujourdhui, $finJournee])
                ->groupBy('etat')
                ->get();

            // Rendez-vous par type aujourd'hui
            $parType = RendezVous::select('type', DB::raw('count(*) as total'))
                ->where('is_deleted', false)
                ->whereBetween('created_at', [$aujourdhui, $finJournee])
                ->groupBy('type')
                ->get();

            return response()->json([
                'total_rendez_vous_aujourdhui' => $totalParJour,
                'rendez_vous_par_etat_aujourdhui' => $parEtat,
                'rendez_vous_par_type_aujourdhui' => $parType,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors du chargement des statistiques d\'aujourd\'hui.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Display a listing of the resource.
     * @permission StatistiqueController::getAllFacture
     * @permission_desc Statistiques de Factures par type de prestation et état
     */
    public function getAllFacture()
    {
        try {
            $today = Carbon::today();

            $factures = Facture::selectRaw("
                DATE(date_fact) as jour,
                prestation_id,
                CASE
                    WHEN state = ? THEN 'Soldé'
                    ELSE 'Non soldé'
                END as etat,
                COUNT(*) as total
            ", [StateFacture::PAID->value])
                ->whereDate('date_fact', $today)
                ->groupByRaw("DATE(date_fact), prestation_id, state")
                ->with('prestation:id,type')
                ->orderByRaw("DATE(date_fact)")
                ->get();

            $data = [];

            foreach ($factures as $facture) {
                $date = $facture->jour;

                $prestationType = $facture->prestation?->type
                    ? TypePrestation::label($facture->prestation->type)
                    : 'Inconnu';

                $etat = $facture->etat;

                if (!isset($data[$date])) {
                    $data[$date] = [];
                }

                if (!isset($data[$date][$prestationType])) {
                    $data[$date][$prestationType] = [
                        'Soldé' => 0,
                        'Non soldé' => 0,
                    ];
                }

                $data[$date][$prestationType][$etat] = $facture->total;
            }

            return response()->json($data);

        } catch (\Throwable $e) {
            Log::error('Erreur dans getAllFacture: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Erreur interne'], 500);
        }
    }


    /**
     * Display a listing of the resource.
     * @permission StatistiqueController::get_client_by_day
     * @permission_desc Etat des clients journalier
     */
    public function get_client_by_day()
    {
        try {
            DB::beginTransaction();
            $today = Carbon::today();

            // Récupération des clients du jour
            $clients = Client::with(["societe", "prefix", "statusFamiliale", "typeDocument", "sexe"])
                ->whereDate('created_at', $today)
                ->orderBy('nomcomplet_client', 'ASC')
                ->get();

            if ($clients->isEmpty()) {
                DB::rollBack();
                return response()->json(['message' => 'Aucun client trouvé aujourd\'hui.'], 404);
            }

            $data = [
                'clients' => $clients,
                'today' => $today,
            ];

            // Nom du fichier PDF
            $fileName   = 'etats-client-' . now()->format('YmdHis') . '.pdf';
            $folderPath = 'storage/etats-clients';
            $filePath   = $folderPath . '/' . $fileName;

            // Créer dossier s'il n'existe pas
            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0755, true);
            }

            // Génération du PDF
            save_browser_shot_pdf(
                view: 'pdfs.etats-clients.etats-clients',
                data: $data,
                folderPath: $folderPath,
                path: $filePath,
                margins: [15, 10, 15, 10]
            );

            DB::commit();

            // Vérifier existence du PDF
            if (!file_exists($filePath)) {
                return response()->json(['message' => 'Le fichier PDF n\'a pas été généré.'], 500);
            }

            // Encodage base64
            $pdfContent = file_get_contents($filePath);
            $base64 = base64_encode($pdfContent);

            return response()->json([
                'clients'  => $clients,
                'base64'   => $base64,
                'url'      => $filePath,
                'filename' => $fileName,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Erreur de validation',
                'details' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Display a listing of the resource.
     * @permission StatistiqueController::get_reglemenets_by_day
     * @permission_desc Etat des caisses journaliers
     */
    public function get_reglemenets_by_day(Request $request)
    {
        try {
            DB::beginTransaction();

            $prestations = Prestation::with([
                'factures.regulations' => fn($q) => $q->where('state', 1),
                'client',
                'consultant',
                'prestationables',
                'centre',
                'priseCharge'
            ])
                ->where('centre_id', $request->header('centre'));

            $titreParts = [];

            if ($request->filled('facture_start') && $request->filled('facture_end')) {
                $start = Carbon::parse($request->facture_start)->startOfDay();
                $end   = Carbon::parse($request->facture_end)->endOfDay();
                $prestations->whereHas('factures.regulations', function ($q) use ($start, $end) {
                    $q->whereBetween('date', [$start, $end]);
                });
                $titreParts[] = "Factures réglées du {$start->format('d/m/Y')} au {$end->format('d/m/Y')}";
            }

            // Filtre par période de prestation
            if ($request->filled('prestation_start') && $request->filled('prestation_end')) {
                $start = Carbon::parse($request->prestation_start)->startOfDay();
                $end   = Carbon::parse($request->prestation_end)->endOfDay();
                $prestations->where(function($query) use ($start, $end) {
                    // Prestations créées dans la période
                    $query->whereBetween('created_at', [$start, $end])
                        // OU prestations dont les règlements sont dans la période
                        ->orWhereHas('factures.regulations', function($q) use ($start, $end) {
                            $q->whereBetween('date', [$start, $end]);
                        });
                });
                $titreParts[] = "Prestations du {$start->format('d/m/Y')} au {$end->format('d/m/Y')}";
            }

            if ($request->filled('prestation_type')) {
                $type = (int) $request->prestation_type;

                if (!array_key_exists($type, TypePrestation::toArray())) {
                    Log::warning("Type invalide : {$type}");
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Type invalide.'
                    ], 422);
                }

                $prestations->where('type', $type);

                // Convertir l'entier en instance d'enum
                $enumInstance = TypePrestation::from($type);

                // Récupérer le label
                $actionLabel = TypePrestation::label($enumInstance);

                // Ajouter le label lisible au titre
                $titreParts[] = "Type prestation : " . $actionLabel;
            }


            // Filtre par mode de règlement
            if ($request->filled('mode_reglement')) {
                $mode = \App\Models\RegulationMethod::find($request->mode_reglement);

                $prestations->whereHas('factures.regulations', function ($q) use ($request) {
                    $q->where('regulation_method_id', $request->mode_reglement);
                });
                $titreParts[] = "Mode de règlement : " . ($mode?->name ?? '');
            }

            // Filtre par date de règlement
            if ($request->filled('reglement_start') && $request->filled('reglement_end')) {
                $start = Carbon::parse($request->reglement_start)->startOfDay();
                $end   = Carbon::parse($request->reglement_end)->endOfDay();
                $prestations->whereHas('factures.regulations', function ($q) use ($start, $end) {
                    $q->whereBetween('date', [$start, $end]);
                });
                $titreParts[] = "Règlements du {$start->format('d/m/Y')} au {$end->format('d/m/Y')}";
            }

            if ($request->filled('assurance')) {
                if ($request->assurance === "assure" && $request->filled('assurance_id')) {
                    $prestations->whereHas('priseCharge', function ($q) use ($request) {
                        $q->where('assureur_id', $request->assurance_id); // Filtrer par l'assurance sélectionnée
                    });
                    $assureur = \App\Models\Assureur::find($request->assurance_id);
                    $titreParts[] = "Avec prise en charge : " . ($assureur ? $assureur->nom : '');
                } elseif ($request->assurance === "non_assure") {
                    $prestations->whereDoesntHave('priseCharge');
                    $titreParts[] = "Sans prise en charge";
                } else {
                    $titreParts[] = "Toutes les prestations";
                }
            }

            // Si aucun filtre n’est fourni, on prend seulement les prestations du jour
            if (
                !$request->filled('facture_start') &&
                !$request->filled('facture_end') &&
                !$request->filled('prestation_start') &&
                !$request->filled('prestation_end') &&
                !$request->filled('mode_reglement') &&
                !$request->filled('reglement_start') &&
                !$request->filled('reglement_end') &&
                !$request->filled('assurance') &&
                !$request->filled('facture_state') &&
                !$request->filled('prestation_type')
            ) {
                $prestations->whereDate('created_at', Carbon::today());
                $titreParts[] = "Prestations du jour";
            }

            // Exécution finale
            $prestations = $prestations->orderBy('created_at', 'ASC')->get();

            if ($prestations->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Aucune donnée trouvée.'
                ], 404);
            }

            $centre = Centre::find($request->header('centre'));
            $media  = $centre->medias()->where('name', 'logo')->first();
            $titre  = implode(" - ", $titreParts);

            $data = [
                'prestations' => $prestations,
                'logo'        => $media ? 'storage/' . $media->path . '/' . $media->filename : '',
                'centre'      => $centre,
                'titre'       => $titre,
            ];

            // -------------------
            // GÉNÉRATION DU PDF
            // -------------------
            $fileName   = 'prestations-clients-' . now()->format('YmdHis') . '.pdf';
            $folderPath = 'storage/prestations-clients';
            $filePath   = $folderPath . '/' . $fileName;

            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0755, true);
            }
            $footer = 'pdfs.reports.factures.footer';

            save_browser_shot_pdf(
                view: 'pdfs.prestations-clients.prestations-clients',
                data: $data,
                folderPath: $folderPath,
                path: $filePath,
                margins: [15, 10, 15, 10],
                footer: $footer
            );

            DB::commit();

            if (!file_exists($filePath)) {
                return response()->json(['message' => 'Le fichier PDF n\'a pas été généré.'], 500);
            }

            $pdfContent = file_get_contents($filePath);
            $base64     = base64_encode($pdfContent);

            return response()->json([
                'prestations' => $prestations,
                'base64'      => $base64,
                'url'         => $filePath,
                'filename'    => $fileName,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error'   => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function get_facture_by_assurances(Request $request)
    {
        try {
            DB::beginTransaction();

            $prestations = Prestation::with([
                'factures.regulations' => fn($q) => $q->where('state', 1),
                'client',
                'consultant',
                'prestationables',
                'centre',
                'priseCharge'
            ])
                ->where('centre_id', $request->header('centre'));

            $titreParts = [];

            if ($request->filled('facture_start') && $request->filled('facture_end')) {
                $start = Carbon::parse($request->facture_start)->startOfDay();
                $end   = Carbon::parse($request->facture_end)->endOfDay();
                $prestations->whereHas('factures.regulations', function ($q) use ($start, $end) {
                    $q->whereBetween('date', [$start, $end]);
                });
                $titreParts[] = "Factures réglées du {$start->format('d/m/Y')} au {$end->format('d/m/Y')}";
            }

            // Filtre par période de prestation
            if ($request->filled('prestation_start') && $request->filled('prestation_end')) {
                $start = Carbon::parse($request->prestation_start)->startOfDay();
                $end   = Carbon::parse($request->prestation_end)->endOfDay();
                $prestations->where(function($query) use ($start, $end) {
                    // Prestations créées dans la période
                    $query->whereBetween('created_at', [$start, $end])
                        // OU prestations dont les règlements sont dans la période
                        ->orWhereHas('factures.regulations', function($q) use ($start, $end) {
                            $q->whereBetween('date', [$start, $end]);
                        });
                });
                $titreParts[] = "Prestations du {$start->format('d/m/Y')} au {$end->format('d/m/Y')}";
            }

            if ($request->filled('prestation_type')) {
                $type = (int) $request->prestation_type;

                if (!array_key_exists($type, TypePrestation::toArray())) {
                    Log::warning("Type invalide : {$type}");
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Type invalide.'
                    ], 422);
                }

                $prestations->where('type', $type);

                $actionLabel = TypePrestation::label($type);
                // Ajouter le label lisible
                $titreParts[] = "Type prestation : " . $actionLabel;
            }




            // Filtre par mode de règlement
            if ($request->filled('mode_reglement')) {
                $mode = \App\Models\RegulationMethod::find($request->mode_reglement);

                $prestations->whereHas('factures.regulations', function ($q) use ($request) {
                    $q->where('regulation_method_id', $request->mode_reglement);
                });
                $titreParts[] = "Mode de règlement : " . ($mode?->name ?? '');
            }

            // Filtre par date de règlement
            if ($request->filled('reglement_start') && $request->filled('reglement_end')) {
                $start = Carbon::parse($request->reglement_start)->startOfDay();
                $end   = Carbon::parse($request->reglement_end)->endOfDay();
                $prestations->whereHas('factures.regulations', function ($q) use ($start, $end) {
                    $q->whereBetween('date', [$start, $end]);
                });
                $titreParts[] = "Règlements du {$start->format('d/m/Y')} au {$end->format('d/m/Y')}";
            }

            if ($request->filled('assurance')) {
                if ($request->assurance === "assure" && $request->filled('assurance_id')) {
                    $prestations->whereHas('priseCharge', function ($q) use ($request) {
                        $q->where('assureur_id', $request->assurance_id); // Filtrer par l'assurance sélectionnée
                    });
                    $assureur = \App\Models\Assureur::find($request->assurance_id);
                    $titreParts[] = "Avec prise en charge : " . ($assureur ? $assureur->nom : '');
                } elseif ($request->assurance === "non_assure") {
                    $prestations->whereDoesntHave('priseCharge');
                    $titreParts[] = "Sans prise en charge";
                } else {
                    $titreParts[] = "Toutes les prestations";
                }
            }

            // 🔹 Filtre client
            if ($request->client === 'client' && $request->filled('client_id')) {
                $prestations->where('client_id', $request->client_id);
                $client = \App\Models\Client::find($request->client_id);
                $titreParts[] = "Client : " . ($client ? $client->nomcomplet_client : '');
            }

            // 🔹 Filtre consultant
            if ($request->consultant === 'consultant' && $request->filled('consultant_id')) {
                $prestations->where('consultant_id', $request->consultant_id);
                $consultant = \App\Models\Consultant::find($request->consultant_id);
                $titreParts[] = "Consultant : " . ($consultant ? $consultant->nomcomplet : '');
            }


            // Si aucun filtre n’est fourni, on prend seulement les prestations du jour
            if (
                !$request->filled('facture_start') &&
                !$request->filled('facture_end') &&
                !$request->filled('prestation_start') &&
                !$request->filled('prestation_end') &&
                !$request->filled('reglement_start') &&
                !$request->filled('assurance') &&
                !$request->filled('client_id') &&
                !$request->filled('consultant_id') &&
                !$request->filled('prestation_type')
            ) {
                $prestations->whereDate('created_at', Carbon::today());
                $titreParts[] = "Prestations du jour";
            }

            // Exécution finale
            $prestations = $prestations->orderBy('created_at', 'ASC')->get();

            if ($prestations->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Aucune donnée trouvée.'
                ], 404);
            }

            $centre = Centre::find($request->header('centre'));
            $media = $centre->medias()->where('name', 'logo')->first();
            $titre = implode(" - ", $titreParts);

            $data = [
                'prestations' => $prestations,
                'logo' => $media ? 'storage/' . $media->path . '/' . $media->filename : '',
                'centre' => $centre,
                'titre' => $titre,
            ];

            // -------------------
            // GÉNÉRATION DU PDF
            // -------------------
            $fileName = 'prestations-assurances-' . now()->format('YmdHis') . '.pdf';
            $folderPath = 'storage/prestations-assurances';
            $filePath = $folderPath . '/' . $fileName;

            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0755, true);
            }
            $footer = 'pdfs.reports.factures.footer';

            save_browser_shot_pdf(
                view: 'pdfs.prestations-assurances.prestations-assurances',
                data: $data,
                folderPath: $folderPath,
                path: $filePath,
                margins: [15, 10, 15, 10],
                footer: $footer,
                format: 'A5',
                direction: 'landscape'
            );

            DB::commit();

            if (!file_exists($filePath)) {
                return response()->json(['message' => 'Le fichier PDF n\'a pas été généré.'], 500);
            }

            $pdfContent = file_get_contents($filePath);
            $base64 = base64_encode($pdfContent);

            return response()->json([
                'prestations' => $prestations,
                'base64' => $base64,
                'url' => $filePath,
                'filename' => $fileName,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }

    }


    /**
     * Display a listing of the resource.
     * @permission StatistiqueController::etat_examens_par_paillasse
     * @permission_desc Etat affichant le nombre d'examens par éléménts de paillases
     */
    public function etat_examens_par_paillasse(Request $request)
    {
        DB::beginTransaction();

        try {
            $start = $request->start_date
                ? Carbon::parse($request->start_date)->startOfDay()
                : Carbon::now()->startOfMonth(); // par défaut début du mois
            $end = $request->end_date
                ? Carbon::parse($request->end_date)->endOfDay()
                : Carbon::now()->endOfMonth(); // par défaut fin du mois
            $centreId = $request->header('centre');
            if (!$centreId) {
                return response()->json([
                    'message' => 'Centre non fourni'
                ], 400);
            }

            $query = DB::table('prestations')
                ->join('prestationables', function ($join) {
                    $join->on('prestations.id', '=', 'prestationables.prestation_id')
                        ->where('prestationables.prestationable_type', Examen::class);
                })
                ->join('examens', 'examens.id', '=', 'prestationables.prestationable_id')
                ->join('paillasses', 'paillasses.id', '=', 'examens.paillasse_id')
                ->whereBetween('prestations.created_at', [$start, $end])
                ->where('prestations.centre_id', $centreId)
                ->where('prestations.regulated', '!=', 3);

            // Filtre optionnel par consultant
            if ($request->consultant_id) {
                $query->where('prestations.consultant_id', $request->consultant_id);
            }

            $rows = $query->select(
                'paillasses.id as paillasse_id',
                'paillasses.name as paillasse',
                'examens.name as examen',
                DB::raw('SUM(prestationables.quantity) as total_examens')
            )
                ->groupBy(
                    'paillasses.id',
                    'paillasses.name',
                    'examens.name'
                )
                ->orderBy('paillasses.name')
                ->get()
                ->groupBy('paillasse');

            if ($rows->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Aucune donnée trouvée.'
                ], 404);
            }

            $centre = Centre::findOrFail($centreId);
            $media  = $centre->medias()->where('name', 'logo')->first();
            $consultant = null;
            if ($request->consultant_id) {
                $consultant = Consultant::find($request->consultant_id);
            }

            $data = [
                'rows'   => $rows,
                'centre'=> $centre,
                'logo'  => $media ? 'storage/' . $media->path . '/' . $media->filename : '',
                'periode' => [
                    'du' => $start->format('d/m/Y'),
                    'au' => $end->format('d/m/Y'),
                ],
                'consultant' => $consultant, // ajouté ici
            ];

            // -------------------
            // GÉNÉRATION DU PDF
            // -------------------
            $fileName   = 'examens-paillasses-' . now()->format('YmdHis') . '.pdf';
            $folderPath = 'storage/examens-paillasses';
            $filePath   = $folderPath . '/' . $fileName;

            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0755, true);
            }
            $footer = 'pdfs.reports.factures.footer';
            save_browser_shot_pdf(
                view: 'pdfs.examens-paillasses.examens-paillasses',
                data: $data,
                folderPath: $folderPath,
                path: $filePath,
                margins: [15, 10, 15, 10],
                format: 'A4',
                footer: $footer
            );

            DB::commit();

            $pdfContent = file_get_contents($filePath);

            return response()->json([
                'base64'   => base64_encode($pdfContent),
                'url'      => $filePath,
                'filename' => $fileName,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error'   => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission StatistiqueController::getFactureInProgressAssurance
     * @permission_desc Afficher les factures en cours des par assurances
     */
    public function getFactureInProgressAssurance(Request $request)
    {
        // Validation
        $request->validate([
            'assurance_id' => ['required', 'exists:assureurs,id'],
            'start_date'   => ['required', 'date'],
            'end_date'     => ['required', 'date'],
            'per_page'     => ['nullable', 'integer'],
            'page'         => ['nullable', 'integer'],
        ]);

        $centreId = $request->header('centre');
        if (!$centreId) {
            return response()->json(['message' => 'Centre non fourni'], 400);
        }

        $start = Carbon::parse($request->start_date)->startOfDay();
        $end   = Carbon::parse($request->end_date)->endOfDay();
        $assuranceId = $request->assurance_id;

        // Récupération des factures avec pagination
        $perPage = $request->input('per_page', 25);
        $page    = $request->input('page', 1);

        $query = Facture::with([
            'prestation.client',
            'prestation.examens',
            'prestation.priseCharge'
        ])
            ->where('state', StateFacture::IN_PROGRESS->value)
            ->whereHas('prestation', function ($q) use ($centreId, $start, $end, $assuranceId) {
                $q->where('centre_id', $centreId)
                    ->whereBetween('created_at', [$start, $end])
                    ->whereHas('priseCharge', function ($pc) use ($assuranceId) {
                        $pc->where('assureur_id', $assuranceId);
                    });
            })
            ->orderBy('created_at', 'desc');

        $factures = $query->paginate($perPage, ['*'], 'page', $page);

        // Calcul du montant total pris en charge
        $totalAmountPc = $query->sum('amount_pc');

        return response()->json([
            'factures'       => $factures->items(),
            'total_amount_pc'=> $totalAmountPc,
            'pagination'     => [
                'current_page'   => $factures->currentPage(),
                'per_page'       => $factures->perPage(),
                'total_items'    => $factures->total(),
                'total_pages'    => $factures->lastPage(),
                'has_more_pages' => $factures->hasMorePages()
            ]
        ], 200);
    }



    /**
     * Display a listing of the resource.
     * @permission StatistiqueController::print_FactureInProgress
     * @permission_desc Imprimer les factures en cours par assurances
     */
    public function print_FactureInProgress(Request $request)
    {
        try {
            $request->validate([
                'assurance_id' => ['required', 'exists:assureurs,id'],
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date'],
            ]);

            $centreId = $request->header('centre');
            if (!$centreId) {
                return response()->json(['message' => 'Centre non fourni'], 400);
            }

            $start = Carbon::parse($request->start_date)->startOfDay();
            $end = Carbon::parse($request->end_date)->endOfDay();
            $assuranceId = $request->assurance_id;

            // -------------------
            // Récupération des factures avec pagination
            // -------------------
            $perPage = $request->input('per_page', 25);
            $page = $request->input('page', 1);

            $factures = Facture::with([
                'prestation.client',
                'prestation.examens',
                'prestation.priseCharge'
            ])
                ->where('state', StateFacture::IN_PROGRESS->value)
                ->whereHas('prestation', function ($q) use ($centreId, $start, $end, $assuranceId) {
                    $q->where('centre_id', $centreId)
                        ->whereBetween('created_at', [$start, $end])
                        ->whereHas('priseCharge', function ($pc) use ($assuranceId) {
                            $pc->where('assureur_id', $assuranceId);
                        });
                })
                ->orderByDesc('created_at')
                ->get(); // <-- ici, plus de paginate

            $totalAmountPc = Facture::where('state', StateFacture::IN_PROGRESS->value)
                ->whereHas('prestation', function ($q) use ($centreId, $start, $end, $assuranceId) {
                    $q->where('centre_id', $centreId)
                        ->whereBetween('created_at', [$start, $end])
                        ->whereHas('priseCharge', function ($pc) use ($assuranceId) {
                            $pc->where('assureur_id', $assuranceId);
                        });
                })
                ->sum('amount_pc');

            if ($factures->isEmpty()) {
                return response()->json([
                    'message' => 'Aucune donnée trouvée.'
                ], 404);
            }

            $centre = Centre::find($centreId);
            $media = $centre?->medias()->where('name', 'logo')->first();

            $data = [
                'factures' => $factures,
                'logo' => $media ? 'storage/' . $media->path . '/' . $media->filename : '',
                'centre' => $centre,
                'totalAmountPc' => $totalAmountPc,
                'startDate' => $start->format('d/m/Y'),  // <-- ici
                'endDate' => $end->format('d/m/Y'),      // <-- et ici
            ];

            // -------------------
            // Génération PDF
            // -------------------
            $fileName = 'FACTURES-ASSURANCES' . now()->format('YmdHis') . '.pdf';
            $folderPath = 'storage/factures-assurances';
            $filePath = $folderPath . '/' . $fileName;

            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0755, true);
            }

            $footer = 'pdfs.reports.factures.footer';

            save_browser_shot_pdf(
                view: 'pdfs.factures-assurances.factures-assurances',
                data: $data,
                folderPath: $folderPath,
                path: $filePath,
                margins: [15, 10, 15, 10],
                footer: $footer
            );

            if (!file_exists($filePath)) {
                return response()->json(['message' => 'Le fichier PDF n\'a pas été généré.'], 500);
            }

            $facturation = \App\Models\FacturationAssurance::updateOrCreate(
                [
                    'assurance_id' => $assuranceId,
                    'start_date' => $start,
                    'end_date' => $end,
                    'centre_id' => $centreId,
                ],
                [
                    'assurance' => $factures->first()->prestation->priseCharge->assureur->nom ?? '',
                    'facture_number' => 'FA-' . now()->format('YmdHis'), // ou garder l'ancien si tu veux
                    'amount' => $totalAmountPc,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]
            );

            $pdfContent = file_get_contents($filePath);
            $base64 = base64_encode($pdfContent);

            // -------------------
            // Retour JSON complet avec pagination
            // -------------------
            return response()->json([
                'factures' => $factures,         // contient data + meta pour Angular
                'totalAmountPc' => $totalAmountPc,
                'base64' => $base64,
                'url' => $filePath,
                'filename' => $fileName,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Erreur de validation.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la génération du PDF.',
                'error' => $e->getMessage()
            ], 500);
        }
    }














    //
}
