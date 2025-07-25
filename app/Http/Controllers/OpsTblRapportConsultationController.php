<?php

namespace App\Http\Controllers;
use App\Models\OpsTblRapportConsultation;
use Illuminate\Http\Request;

class OpsTblRapportConsultationController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission OpsTblRapportConsultationController::index
     * @permission_desc Afficher des rapports de consultation pour les dossiers clients
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = OpsTblRapportConsultation::where('is_deleted', false)
            ->with([
                'creator',
                'updater',
                'dossierConsultation:id,id,code,created_at,rendez_vous_id',
                'dossierConsultation.rendezVous:id,id,client_id,consultant_id,dateheure_rdv',
                'dossierConsultation.rendezVous.client',
                'dossierConsultation.rendezVous.consultant:id,id,nomcomplet,ref',
            ]);

        // Recherche globale
        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%$search%")
                    ->orWhere('conclusion', 'like', "%$search%")
                    ->orWhere('recommandations', 'like', "%$search%")
                    ->orWhereHas('dossierConsultation', function ($q2) use ($search) {
                        $q2->where('code', 'like', "%$search%"); // ou selon la colonne affichable
                    });
            });
        }

        $results = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $results->items(),
            'current_page' => $results->currentPage(),
            'last_page' => $results->lastPage(),
            'total' => $results->total(),
        ]);
    }


    /**
     * Display a listing of the resource.
     * @permission OpsTblRapportConsultationController::getHistoriqueRapportClient
     * @permission_desc Afficher l'historique des rapports de consultation d'un client
     */
    public function getHistoriqueRapportClient(Request $request, $client_id)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = OpsTblRapportConsultation::where('is_deleted', false)
            ->whereHas('dossierConsultation.rendezVous', function ($query) use ($client_id) {
                $query->where('client_id', $client_id);
            })
            ->with([
                'dossierConsultation:id,code,rendez_vous_id',
                'dossierConsultation.rendezVous:id,dateheure_rdv,code,client_id',
            ])
            ->orderByDesc('created_at');

        $results = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $results->items(),
            'current_page' => $results->currentPage(),
            'last_page' => $results->lastPage(),
            'total' => $results->total(),
        ]);
    }


    /**
     * Display a listing of the resource.
     * @permission OpsTblRapportConsultationController::store
     * @permission_desc Enregistrer des rapports de consultation pour les dossiers clients
     */
    public function store(Request $request)
    {
        $auth = auth()->user();
        $request->validate([
            'resume' => 'required|string',
            'conclusion' => 'nullable|string',
            'recommandations' => 'nullable|string',
            'dossier_consultation_id' => 'required|exists:dossier_consultations,id',
        ]);

        $rapport = OpsTblRapportConsultation::create([
            'resume' => $request->resume,
            'conclusion' => $request->conclusion,
            'recommandations' => $request->recommandations,
            'dossier_consultation_id' => $request->dossier_consultation_id,
            'created_by' => $auth->id
        ]);

        return response()->json([
            'message' => 'Rapport de consultation enregistré avec succès.',
            'data' => $rapport
        ], 201);
    }

    /**
     * Display a listing of the resource.
     * @permission OpsTblRapportConsultationController::store
     * @permission_desc Mettre à jour des rapports de consultation pour les dossiers clients
     */

    public function update(Request $request, $id)
    {
        $auth = auth()->user();
        $rapport = OpsTblRapportConsultation::findOrFail($id);

        $rapport->update([
            'resume' => $request->resume,
            'conclusion' => $request->conclusion,
            'recommandations' => $request->recommandations,
            'motif_consultation_id' => $request->motif_consultation_id,
            'updated_by' => $auth->id
        ]);

        return response()->json([
            'message' => 'Rapport mis à jour avec succès.',
            'data' => $rapport
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission OpsTblRapportConsultationController::store
     * @permission_desc Afficher les détails spécifiques des rapports de consultation pour les dossiers clients
     */
    public function show($id)
    {
        $rapport =  OpsTblRapportConsultation::where('is_deleted', false)
            ->with([
                'creator:id,login',
                'updater:id,login',
                'dossierConsultation:id,id,code,created_at,rendez_vous_id',
                'dossierConsultation.rendezVous:id,id,client_id,consultant_id,dateheure_rdv',
                'dossierConsultation.rendezVous.client',
                'dossierConsultation.rendezVous.consultant',
            ])->findOrFail($id);

        return response()->json([
            'rapport' => $rapport
        ]);
    }
    //
}
