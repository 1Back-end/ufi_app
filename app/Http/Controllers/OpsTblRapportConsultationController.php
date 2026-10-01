<?php

namespace App\Http\Controllers;
use App\Exports\OpsTblRapportConsultationsExport;
use App\Exports\RendezVousExport;
use App\Models\DossierConsultation;
use App\Models\OpsTblRapportConsultation;
use App\Models\RendezVous;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;


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

        $query = OpsTblRapportConsultation::with([
            'creator',
            'updater',
            'dossierConsultation.rendezVous.client',
            'dossierConsultation.rendezVous.consultant'
        ]);

        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->input('search');

            $q->where(function ($subQ) use ($search) {
                $subQ->where('code', 'like', "%$search%")
                    ->orWhere('conclusion', 'like', "%$search%")
                    ->orWhere('recommandations', 'like', "%$search%")

                    ->orWhereHas('dossierConsultation', function ($qDossier) use ($search) {
                        $qDossier->where('code', 'like', "%$search%");
                    })
                    ->orWhereHas('dossierConsultation.rendezVous.client', function ($qClient) use ($search) {
                        $qClient->where('nom_cli', 'like', "%$search%")
                            ->orWhere('prenom_cli', 'like', "%$search%")
                            ->orWhere('secondprenom_cli', 'like', "%$search%");
                    })
                    ->orWhereHas('dossierConsultation.rendezVous.consultant', function ($qConsultant) use ($search) {
                        $qConsultant->where('nom', 'like', "%$search%")
                            ->orWhere('prenom', 'like', "%$search%")
                            ->orWhere('nomcomplet', 'like', "%$search%")
                            ->orWhere('code_hopi', 'like', "%$search%")
                            ->orWhere('ref', 'like', "%$search%");
                    });
            });
        });

        $query->when($request->filled('consultant_id'), function ($q) use ($request) {
            $consultantId = $request->input('consultant_id');
            $q->whereHas('dossierConsultation.rendezVous', function ($qRdv) use ($consultantId) {
                $qRdv->where('consultant_id', $consultantId);
            });
        });

        $query->when($request->filled('date'), function ($q) use ($request) {
            $date = $request->input('date');
            $q->whereDate('created_at', $date);
        });

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
     * @permission OpsTblRapportConsultationController::show
     * @permission_desc Afficher les détails spécifiques d'un rapport de consultation
     */
    public function show($id)
    {
        $rapport =  OpsTblRapportConsultation::with([
            'creator',
            'updater',
            'dossierConsultation.rendezVous.client',
            'dossierConsultation.rendezVous.consultant'
            ])->findOrFail($id);

        return response()->json([
            'rapport' => $rapport
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission OpsTblRapportConsultationController::export_in_excel
     * @permission_desc Exporter les rapports de rendez vous en excel
     */
    public function export_in_excel()
    {
        try {
            $fileName = Str::upper('rapports-de-rendez-vous-' . Carbon::now()->format('Y-m-d_H-i-s') . '.xlsx');
            Excel::store(new OpsTblRapportConsultationsExport(), $fileName, 'reports_folder_patients');
            return response()->json([
                "message" => "Exportation des données effectuée avec succès",
                "filename" => $fileName,
                "url" => Storage::disk('reports_folder_patients')->url($fileName)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "message" => "Une erreur est survenue lors de l'exportation des données.",
                "error" => $e->getMessage()
            ], 500);
        }
    }
    //
}
