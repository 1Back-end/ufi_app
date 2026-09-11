<?php

namespace App\Http\Controllers;
use App\Models\DossierConsultation;
use App\Models\OpsTblRapportConsultation;
use App\Models\RendezVous;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


/**
 * @permission_category Gestion des rapports de consultations
 * @permission_module Gestion des prestations
 */
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


        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {

                $q->where('code', 'like', "%$search%")
                    ->orWhere('conclusion', 'like', "%$search%")
                    ->orWhere('recommandations', 'like', "%$search%")


                    ->orWhereHas('dossierConsultation', function ($q2) use ($search) {
                        $q2->where('code', 'like', "%$search%");
                    })

                    ->orWhereHas('dossierConsultation.rendezVous.client', function ($q3) use ($search) {
                        $q3->where('nomcomplet_client', 'like', "%$search%")
                            ->orWhere('ref_cli', 'like', "%$search%");
                    })

                    ->orWhereHas('dossierConsultation.rendezVous.consultant', function ($q4) use ($search) {
                        $q4->where('nomcomplet', 'like', "%$search%")
                            ->orWhere('ref', 'like', "%$search%");
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
            'resume'                  => 'required|string',
            'conclusion'              => 'required|string',
            'recommandations'         => 'required|string',
            'dossier_consultation_id' => 'required|exists:dossier_consultations,id',
        ]);

        $dossier = DossierConsultation::find($request->dossier_consultation_id);
        if (!$dossier || (!$dossier->is_have_examen_physique && !$dossier->is_have_enquete_systemique)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible d\'enregistrer le rapport : vous devez d\'abord renseigner au moins un examen physique ou une enquête systémique.'
            ], 422);
        }

        try {
            $result = DB::transaction(function () use ($request, $auth) {

                $rapport = OpsTblRapportConsultation::create([
                    'resume'                  => $request->resume,
                    'conclusion'              => $request->conclusion,
                    'recommandations'         => $request->recommandations,
                    'dossier_consultation_id' => $request->dossier_consultation_id,
                    'created_by'              => $auth->id ?? null,
                ]);

                $dossier = DossierConsultation::find($request->dossier_consultation_id);

                $dossier->update([
                    'is_have_rapport_consultation' => true
                ]);


                return $rapport;
            });

            return response()->json([
                'message' => 'Rapport de consultation enregistré avec succès.',
                'data'    => $result
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => "Erreur lors de l'enregistrement du rapport.",
                'error'   => $e->getMessage()
            ], 500);
        }
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
