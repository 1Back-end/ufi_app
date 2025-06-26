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
                'creator:id,login',
                'updater:id,login',
                'dossierConsultation:id,id,code,created_at,rendez_vous_id',
                'dossierConsultation.rendezVous:id,id,client_id,consultant_id,dateheure_rdv',
                'dossierConsultation.rendezVous.client:id,id,nomcomplet_client,ref_cli',
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
     * @permission OpsTblRapportConsultationController::store
     * @permission_desc Enregistrer des rapports de consultation pour les dossiers clients
     */
    public function store(Request $request)
    {
        $auth = auth()->user();
        $request->validate([
            'conclusion' => 'nullable|string',
            'recommandations' => 'nullable|string',
            'dossier_consultation_id' => 'required|exists:dossier_consultations,id',
        ]);

        $rapport = OpsTblRapportConsultation::create([
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
                'dossierConsultation.rendezVous.client:id,id,nomcomplet_client,ref_cli',
                'dossierConsultation.rendezVous.consultant:id,id,nomcomplet,ref',
            ])->findOrFail($id);

        return response()->json([
            'rapport' => $rapport
        ]);
    }
    //
}
