<?php

namespace App\Http\Controllers;

use App\Models\BilanActeRendezVous;
use App\Models\OpsTbl_Examen_Physique;
use App\Models\RendezVous;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
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







    //
}
