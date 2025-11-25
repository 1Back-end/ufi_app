<?php

namespace App\Http\Controllers;

use App\Enums\StateFacture;
use App\Enums\TypeClient;
use App\Enums\TypePrestation;
use App\Models\Centre;
use App\Models\Client;
use App\Models\Facture;
use App\Models\Prestation;
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

            // Filtre par période de factures
            if ($request->filled('facture_start') && $request->filled('facture_end')) {
                $start = Carbon::parse($request->facture_start)->startOfDay();
                $end   = Carbon::parse($request->facture_end)->endOfDay();
                $prestations->whereHas('factures', fn($q) => $q->whereBetween('date_fact', [$start, $end]));
                $titreParts[] = "Factures du " . $start->format('d/m/Y') . " au " . $end->format('d/m/Y');
            }

            // Filtre par période de prestation
            if ($request->filled('prestation_start') && $request->filled('prestation_end')) {
                $start = Carbon::parse($request->prestation_start)->startOfDay();
                $end   = Carbon::parse($request->prestation_end)->endOfDay();
                $prestations->whereBetween('created_at', [$start, $end]);
                $titreParts[] = "Prestations du " . $start->format('d/m/Y') . " au " . $end->format('d/m/Y');
            }

            // Filtre par mode de règlement
            if ($request->filled('mode_reglement')) {
                $mode = \App\Models\RegulationMethod::find($request->mode_reglement);
                $prestations->whereHas('factures.regulations', fn($q) => $q->where('regulation_method_id', $request->mode_reglement));
                $titreParts[] = "Mode de règlement: " . ($mode ? $mode->name : '');
            }

            // Filtre par date de règlement
            if ($request->filled('reglement_start') && $request->filled('reglement_end')) {
                $start = Carbon::parse($request->reglement_start)->startOfDay();
                $end   = Carbon::parse($request->reglement_end)->endOfDay();
                $prestations->whereHas('factures.regulations', fn($q) => $q->whereBetween('date', [$start, $end]));
                $titreParts[] = "Règlements du " . $start->format('d/m/Y') . " au " . $end->format('d/m/Y');
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
                !$request->filled('facture_state')
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


            // Filtre par période de factures
            if ($request->filled('facture_start') && $request->filled('facture_end')) {
                $start = Carbon::parse($request->facture_start)->startOfDay();
                $end   = Carbon::parse($request->facture_end)->endOfDay();
                $prestations->whereHas('factures', fn($q) => $q->whereBetween('date_fact', [$start, $end]));
                $titreParts[] = "Factures du " . $start->format('d/m/Y') . " au " . $end->format('d/m/Y');
            }

            // Filtre par période de prestation
            if ($request->filled('prestation_start') && $request->filled('prestation_end')) {
                $start = Carbon::parse($request->prestation_start)->startOfDay();
                $end   = Carbon::parse($request->prestation_end)->endOfDay();
                $prestations->whereBetween('created_at', [$start, $end]);
                $titreParts[] = "Prestations du " . $start->format('d/m/Y') . " au " . $end->format('d/m/Y');
            }


            // Filtre par assurance
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
                !$request->filled('consultant_id')
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
            $fileName   = 'prestations-assurances-' . now()->format('YmdHis') . '.pdf';
            $folderPath = 'storage/prestations-assurances';
            $filePath   = $folderPath . '/' . $fileName;

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







    //
}
