<?php

namespace App\Http\Controllers;

use App\Exports\DossierConsultationExport;
use App\Exports\MotifExportSearch;
use App\Exports\MotifsExport;
use App\Models\OpsTbl_Motif_consultation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class OpsTblMotifConsultationController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission OpsTblMotifConsultationController::index
     * @permission_desc Afficher la liste des motifs de consultation
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = OpsTbl_Motif_consultation::where('is_deleted', false)
            ->with([
                'dossierConsultation:id,code,rendez_vous_id',
                'dossierConsultation.rendezVous.client:id,nomcomplet_client,ref_cli',
                'creator:id,login',
                'updater:id,login',
            ]);

        // Filtrage direct (optionnel)
        if ($request->filled('dossier_consultation_id')) {
            $query->where('dossier_consultation_id', $request->input('dossier_consultation_id'));
        }

        // Recherche globale
        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%")
                    ->orWhere('libelle', 'like', "%$search%")
                    ->orWhereHas('dossierConsultation', function ($q2) use ($search) {
                        $q2->where('code', 'like', "%$search%")
                            ->orWhere('rendez_vous_id', 'like', "%$search%")
                            ->orWhereHas('rendezVous', function ($q3) use ($search) {
                                $q3->where('code', 'like', "%$search%")
                                    ->orWhere('client_id', 'like', "%$search%")
                                    ->orWhereHas('client', function ($q4) use ($search) {
                                        $q4->where('nomcomplet_client', 'like', "%$search%")
                                            ->orWhere('ref_cli', 'like', "%$search%");
                                    });
                            });
                    });
            });
        }

        $motifs = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $motifs->items(),
            'current_page' => $motifs->currentPage(),
            'last_page' => $motifs->lastPage(),
            'total' => $motifs->total(),
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission OpsTblMotifConsultationController::store
     * @permission_desc Enregistrer des motifs de consultation
     */

    public function store(Request $request)
    {
        $auth = auth()->user();

        $request->validate([
            'libelle'=> 'nullable|string',
            'description' => 'required|string',
            'dossier_consultation_id' => 'required|exists:dossier_consultations,id',
            'categorie_visite_id' => 'required|exists:config_tbl_categorie_visites,id',
            'type_visite_id' => 'nullable|exists:config_tbl_type_visite,id',

        ]);

//        // Vérifie si le dossier a déjà un motif
//        $motifExistant = OpsTbl_Motif_consultation::where('dossier_consultation_id', $request->dossier_consultation_id)
//            ->where('is_deleted', false)
//            ->first();
//
//        if ($motifExistant) {
//            return response()->json([
//                'message' => 'Ce dossier a déjà un motif de consultation.'
//            ], 422);
//        }

        // Création du nouveau motif
        $motif = OpsTbl_Motif_consultation::create([
            'code' => 'MOTF-' . strtoupper(uniqid()),
            'description' => $request->description,
            'libelle'=> $request->libelle,
            'dossier_consultation_id' => $request->dossier_consultation_id,
            'categorie_visite_id' => $request->categorie_visite_id,
            'type_visite_id' => $request->type_visite_id,
            'created_by' => $auth->id
        ]);

        return response()->json([
            'message' => 'Motif ajouté avec succès.',
            'data' => $motif
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission OpsTblMotifConsultationController::export
     * @permission_desc Exporter les motifs de consultation
     */

    public function export()
    {
        $fileName = 'motifs-consultations-' . Carbon::now()->format('Y-m-d') . '.xlsx';

        Excel::store(new MotifsExport(), $fileName, 'motifsconsultations');

        return response()->json([
            "message" => "Exportation des données effectuée avec succès",
            "filename" => $fileName,
            "url" => Storage::disk('motifsconsultations')->url($fileName)
        ]);
    }
    /**
     * Display a listing of the resource.
     * @permission OpsTblMotifConsultationController::search_and_export
     * @permission_desc Rechercher et Exporter les motifs de consultation
     */
    public function search_and_export(Request $request){
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = OpsTbl_Motif_consultation::where('is_deleted', false)
            ->with([
                'dossierConsultation:id,code,rendez_vous_id',
                'dossierConsultation.rendezVous:id,code,client_id',
                'dossierConsultation.rendezVous.client:id,nomcomplet_client,ref_cli',
                'creator:id,login',
                'updater:id,login',
            ]);

        // Filtrage direct (optionnel)
        if ($request->filled('dossier_consultation_id')) {
            $query->where('dossier_consultation_id', $request->input('dossier_consultation_id'));
        }

        // Recherche globale
        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%")
                    ->orWhereHas('dossierConsultation', function ($q2) use ($search) {
                        $q2->where('code', 'like', "%$search%")
                            ->orWhere('rendez_vous_id', 'like', "%$search%")
                            ->orWhereHas('rendezVous', function ($q3) use ($search) {
                                $q3->where('code', 'like', "%$search%")
                                    ->orWhere('client_id', 'like', "%$search%")
                                    ->orWhereHas('client', function ($q4) use ($search) {
                                        $q4->where('nomcomplet_client', 'like', "%$search%")
                                            ->orWhere('ref_cli', 'like', "%$search%");
                                    });
                            });
                    });
            });
        }

        $motifsPaginated = $query->latest()->paginate($perPage, ['*'], 'page', $page);
        if ($motifsPaginated->isEmpty()) {
            return response()->json([
                'message' => 'Aucune donnée trouvée pour cette recherche.',
                'data' => []
            ]);
        }

        // Récupération de la collection complète (sans pagination) pour l'export
        $motifsToExport = $query->get();

        $fileName = 'motif-consultations-recherches-' . now()->format('Y-m-d-His') . '.xlsx';
        Excel::store(new MotifExportSearch($motifsToExport), $fileName, 'motifsconsultations');

        return response()->json([
            "message" => "Exportation des données effectuée avec succès",
            "filename" => $fileName,
            "url" => Storage::disk('motifsconsultations')->url($fileName),
            "data" => $motifsPaginated->items(),
            "current_page" => $motifsPaginated->currentPage(),
            "last_page" => $motifsPaginated->lastPage(),
            "total" => $motifsPaginated->total(),
        ]);


    }

    //
}
