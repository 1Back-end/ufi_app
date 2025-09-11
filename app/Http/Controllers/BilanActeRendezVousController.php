<?php

namespace App\Http\Controllers;

use App\Models\BilanActeRendezVous;
use App\Models\OpsTbl_Examen_Physique;
use App\Models\RendezVous;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;
use Throwable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Spatie\Browsershot\Exceptions\CouldNotTakeBrowsershot;
/**
 * @permission_category Gestion du bilan des actes
 */


class BilanActeRendezVousController extends Controller
{
    public function index(){

    }

    /**
     * Display a listing of the resource.
     * @permission BilanActeRendezVousController::getHistoriqueActesClient
     * @permission_desc Afficher les rapports de rendez vous de types(Actes) d'un client
     */

    public function getHistoriqueActesClient(Request $request, int $client_id)
    {
        try {
            $perPage = $request->input('limit', 25);
            $page    = $request->input('page', 1);

            $results = BilanActeRendezVous::query()
                ->whereHas('rendezVous', fn ($q) => $q->where('client_id', $client_id))
                ->with([
                    'rendezVous',
                    'rendezVous.consultant',
                    'prestation',
                    'creator',
                    'updater'
                ])
                ->orderByDesc('created_at')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'data'         => $results->items(),
                'current_page' => $results->currentPage(),
                'last_page'    => $results->lastPage(),
                'total'        => $results->total(),
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            // Journalisation pour le debug
            Log::error('Erreur getHistoriqueActesClient', [
                'client_id' => $client_id,
                'message'   => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            // Réponse générique côté client
            return response()->json([
                'error'   => true,
                'message' => 'Une erreur est survenue lors de la récupération de l’historique.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Display a listing of the resource.
     * @permission BilanActeRendezVousController::store
     * @permission_desc Enregistrer le au rendez de vous de type(Actes)
     */

    public function store(Request $request)
    {
        $auth = auth()->user();

        $request->validate([
            'rendez_vous_id'       => 'required|exists:rendez_vouses,id',
            'prestation_id'        => 'required|exists:prestations,id',
            'consultant_id'        => 'required|exists:consultants,id',
            'technique_analyse_id' => 'required|exists:analysis_techniques,id',
            'resume'               => 'nullable|string',
            'conclusion'           => 'nullable|string',
        ]);

        // Création du bilan avec les nouveaux champs
        $bilan = BilanActeRendezVous::create([
            'rendez_vous_id'       => $request->rendez_vous_id,
            'prestation_id'        => $request->prestation_id,
            'consultant_id'        => $request->consultant_id,
            'technique_analyse_id' => $request->technique_analyse_id,
            'resume'               => $request->resume,
            'conclusion'           => $request->conclusion,
            'created_by'           => $auth->id,
            'updated_by'           => $auth->id,
        ]);

        // Mise à jour de l’état du rendez‑vous à “Clos”
        RendezVous::where('id', $request->rendez_vous_id)
            ->update(['etat' => 'Clos']);

        return response()->json([
            'message' => 'Bilan enregistré et rendez‑vous clôturé avec succès.',
            'data'    => $bilan,
        ], 201);
    }


    /**
     * Display a listing of the resource.
     * @permission BilanActeRendezVousController::update
     * @permission_desc Modifier le au rendez de vous de type(Actes)
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();

        $request->validate([
            'rendez_vous_id' => 'required|exists:rendez_vouses,id',
            'prestation_id'        => 'required|exists:prestations,id',
            'resume'         => 'nullable|string',
            'conclusion'     => 'nullable|string',
        ]);

        $bilan = BilanActeRendezVous::findOrFail($id);

        $bilan->update([
            'rendez_vous_id' => $request->rendez_vous_id,
            'prestation_id'        => $request->prestation_id,
            'resume'         => $request->resume,
            'conclusion'     => $request->conclusion,
            'updated_by'     => $auth->id,
        ]);

        return response()->json([
            'message' => 'Bilan mis à jour avec succès.',
            'data'    => $bilan,
        ], 200);
    }

    /**
     * Display a listing of the resource.
     * @permission BilanActeRendezVousController::show
     * @permission_desc Afficher les détails d'un rendez de vous de type(Actes)
     */
    public function show($id)
    {
        $bilan = BilanActeRendezVous::with(['rendezVous', 'prestation', 'creator', 'updater'])->findOrFail($id);

        return response()->json([
            'message' => 'Détails du bilan récupérés avec succès.',
            'data'    => $bilan,
        ], 200);
    }



    /**
     * Display a listing of the resource.
     * @permission BilanActeRendezVousController::PrintRapport
     * @permission_desc Imprimer le rapport des actes du client
     */
    public function PrintRapport(Request $request, int $client_id)
    {
        DB::beginTransaction();

        try {
            $rapports = BilanActeRendezVous::query()
                ->whereHas('rendezVous', fn ($q) => $q->where('client_id', $client_id))
                ->with([
                    'rendezVous',
                    'rendezVous.consultant',
                    'techniqueAnalyse',
                    'consultant',
                    'prestation.actes', // uniquement les actes
                    'creator',
                    'updater'
                ])
                ->get();

            if ($rapports->isEmpty()) {
                DB::rollBack();
                return response()->json(['message' => 'Aucun rapport trouvé pour ce client.'], 404);
            }

            $data = [
                'rapports' => $rapports,
                'client'   => $rapports->first()->rendezVous->client ?? null,
            ];
//            dd($data);

            $fileName   = 'rapport-client-' . $client_id . '-' . now()->format('YmdHis') . '.pdf';
            $folderPath = 'storage/rapport-clients'; // chemin absolu
            $filePath   = $folderPath . '/' . $fileName;

            // Création dossier si nécessaire
            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0755, true);
            }

            // Génération PDF
            save_browser_shot_pdf(
                view: 'pdfs.rapport-clients.rapport-client',
                data: $data,
                folderPath: $folderPath,
                path: $filePath,
                margins: [15, 10, 15, 10]
            );

            DB::commit();

            // Lecture du contenu PDF et encodage base64
            if (!file_exists($filePath)) {
                return response()->json(['message' => 'Le fichier PDF n\'a pas été généré.'], 500);
            }

            $pdfContent = file_get_contents($filePath);
            $base64 = base64_encode($pdfContent);

            return response()->json([
                'rapports' => $rapports,
                'base64'   => $base64,
                'url' => $filePath,
                'filename' => $fileName,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Erreur de validation',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }










    //
}
