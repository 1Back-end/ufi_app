<?php

namespace App\Http\Controllers;

use App\Enums\TypePrestation;
use App\Models\Centre;
use App\Models\Prestation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @permission_category Gestion des rapports
 * @permission_module Gestion des rapports
 */
class ReportController extends Controller
{
    public function print_data_validated(Request $request)
    {
        try {
            DB::beginTransaction();

            $prestations = Prestation::with([
                'factures.regulations' => fn($q) => $q->where('state', 3),
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

            if ($request->filled('type_prestation')) {
                $prestations->where('type', $request->type_prestation);

                $titreParts[] = "Filtre : type de prestation ({$request->type_prestation})";
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
            $fileName = strtoupper('etats-des-prestations-factures-' . now()->format('YmdHis')) . '.pdf';
            $folderPath = 'storage/etats-des-prestations-factures';
            $filePath = $folderPath . '/' . $fileName;

            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0755, true);
            }
            $footer = 'pdfs.reports.factures.footer';

            save_browser_shot_pdf(
                view: 'pdfs.etats-des-prestations-factures.etats-des-prestations-factures',
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



    public function print_data_not_close(Request $request)
    {
        try {
            DB::beginTransaction();

            $prestations = Prestation::with([
                'factures.regulations' => fn($q) => $q->where('state', 3),
                'client',
                'consultant',
                'prestationables',
                'centre',
                'priseCharge',
                'payableBy'
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

            if ($request->filled('type_prestation')) {
                $prestations->where('type', $request->type_prestation);

                $titreParts[] = "Filtre : type de prestation ({$request->type_prestation})";
            }

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
            $fileName = strtoupper('etats-des-prestations-non-factures-' . now()->format('YmdHis')) . '.pdf';
            $folderPath = 'storage/etats-des-prestations-factures';
            $filePath = $folderPath . '/' . $fileName;

            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0755, true);
            }
            $footer = 'pdfs.reports.factures.footer';

            save_browser_shot_pdf(
                view: 'pdfs.etats-des-prestations-non-factures.etats-des-prestations-non-factures',
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


    public function print_data_not_assurance_with_associate(Request $request)
    {
        try {
            DB::beginTransaction();

            $prestations = Prestation::with([
                'factures.regulations' => function ($q) {
                    $q->whereIn('state', [1, 2]);
                },
                'client',
                'consultant',
                'prestationables',
                'centre',
                'priseCharge',
                'payableBy'
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

            if ($request->filled('type_prestation')) {
                $prestations->where('type', $request->type_prestation);

                $titreParts[] = "Filtre : type de prestation ({$request->type_prestation})";
            }

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

            if ($request->filled('payable_by_mode')) {

                // 🔵 TOUS les clients payeurs
                if ($request->payable_by_mode === "all") {

                    $prestations->whereHas('payableBy'); // ✔ plus fiable que whereNotNull

                    $titreParts[] = "Toutes les prestations avec client associé";
                }

                elseif ($request->payable_by_mode === "one" && $request->filled('payable_by_id')) {

                    $prestations->where('payable_by', $request->payable_by_id);

                    $client = \App\Models\Client::find($request->payable_by_id);

                    $titreParts[] = "Client associé : " . ($client?->nomcomplet_client ?? '');
                }
                elseif ($request->payable_by_mode === "none") {

                    $prestations->whereNull('payable_by');

                    $titreParts[] = "Sans client associé";
                }
            }
            if (
                !$request->filled('facture_start') &&
                !$request->filled('payable_by_mode') &&
                !$request->filled('facture_end') &&
                !$request->filled('prestation_start') &&
                !$request->filled('prestation_end') &&
                !$request->filled('reglement_start') &&
                !$request->filled('assurance') &&
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
            $fileName = strtoupper('etats-des-prestations-global_assurances_et_associes-' . now()->format('YmdHis')) . '.pdf';
            $folderPath = 'storage/etats-des-prestations-factures';
            $filePath = $folderPath . '/' . $fileName;

            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0755, true);
            }
            $footer = 'pdfs.reports.factures.footer';

            save_browser_shot_pdf(
                view: 'pdfs.etats-des-prestations-global_assurances_et_associes.etats-des-prestations-global_assurances_et_associes',
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
}
