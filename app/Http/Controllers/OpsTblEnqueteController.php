<?php

namespace App\Http\Controllers;

use App\Exports\ExamenEnqueteExport;
use App\Exports\MotifsExport;
use App\Models\OpsTbl_Examen_Physique;
use App\Models\OpsTblEnquete;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @permission_category Gestion des enquete systémiques
 */
class OpsTblEnqueteController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission OpsTblEnqueteController::index
     * @permission_desc Afficher la liste des enquete systémiques pour les dossiers de consultations
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = OpsTblEnquete::where('is_deleted', false)
            ->with([
                'creator:id,login',
                'updater:id,login',
                'categorieEnquete:id,name',
                'motifConsultation:id,libelle,code,description,dossier_consultation_id',
                'motifConsultation.dossierConsultation:id,code'
            ]);


        if ($request->filled('motif_consultation_id')) {
            $query->where('motif_consultation_id', $request->motif_consultation_id);
        }


        if ($request->filled('categories_enquetes_id')) {
            $query->where('categories_enquetes_id', $request->categories_enquetes_id);
        }


        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%$search%")
                    ->orWhere('libelle', 'like', "%$search%")
                    ->orWhere('resultat', 'like', "%$search%")
                    ->orWhereHas('categorieEnquete', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%$search%")
                            ->orWhere('description', 'like', "%$search%")
                            ->orWhere('id', 'like', "%$search%");
                    })
                    ->orWhereHas('motifConsultation', function ($q2) use ($search) {
                        $q2->where('libelle', 'like', "%$search%")
                            ->orWhere('code', 'like', "%$search%")
                            ->orWhere('description', 'like', "%$search%")
                            ->orWhere('id', 'like', "%$search%");
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
     * @permission OpsTblEnqueteController::getHistoriqueEnqueteClient
     * @permission_desc Afficher l'historique des enquete systémiques d'un client
     */
    public function getHistoriqueEnqueteClient(Request $request, $client_id)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = OpsTblEnquete::where('is_deleted', false)
            ->whereHas('dossierConsultation.rendezVous', function ($query) use ($client_id) {
                $query->where('client_id', $client_id);
            })
            ->with([
                'categorieEnquete:id,name',
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
     * @permission OpsTblEnqueteController::store
     * @permission_desc Creer des enquetes pour des dossiers de consultations
     */
    public function store(Request $request)
    {
        $auth = auth()->user();
        $validated = $request->validate([
            'enquetes' => 'required|array|min:1',
            'enquetes.*.libelle' => 'required|string|max:255',
            'enquetes.*.resultat' => 'nullable|string',
            'enquetes.*.categories_enquetes_id' => 'required|exists:configtbl_categories_enquetes,id',
            'enquetes.*.dossier_consultation_id' => 'required|exists:dossier_consultations,id',
        ]);

        $created = [];

        foreach ($validated['enquetes'] as $examenData) {
            $examenData['created_by'] = $auth->id;
            $created[] = OpsTblEnquete::create($examenData);
        }
        return response()->json([
            'message' => 'Enquêtes enregistrées avec succès.',
            'data' => $created
        ]);
    }


    /**
     * Display a listing of the resource.
     * @permission OpsTblEnqueteController::show
     * @permission_desc Afficher les détails des enquetes pour des dossiers de consultations
     */
    public function show(string $id)
    {
        $enquete = OpsTblEnquete::with(['categorieEnquete:id,name'])
            ->where('is_deleted', false)
            ->where('id', $id)
            ->first();

        if (!$enquete) {
            return response()->json(["message" => "Enquête introuvable"], 404);
        }

        return response()->json([
            'enquete' => $enquete
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission OpsTblEnqueteController::update
     * @permission_desc Modification  des enquetes pour des dossiers de consultations
     */
    public function update(Request $request, $id)
    {
        $enquete = OpsTblEnquete::where('is_deleted', false)->find($id);
        if(!$enquete){
            return response()->json(["message" => "Enquete introuvable"], 404);
        }
        $auth = auth()->user();
        $request->validate([
            'libelle' => 'required|string',
            'resultat' => 'nullable|string',
            'categories_enquetes_id' => 'required|exists:configtbl_categories_enquetes,id',
        ]);

        $enquete->update([
            'libelle' => $request->libelle,
            'resultat' => $request->resultat,
            'categories_enquetes_id' => $request->categories_enquetes_id,
            'updated_by' => $auth->id
        ]);

        return response()->json([
            'message' => 'Enquête mise à jour avec succès',
            'data' => $enquete,
        ]);
    }
    /**
     * Display a listing of the resource.
     * @permission OpsTblEnqueteController::export
     * @permission_desc Exporter les enquetes systémiques pour des dossiers de consultations
     */
    public function export()
    {
        $fileName = 'examens-enquetes-' . Carbon::now()->format('Y-m-d') . '.xlsx';

        Excel::store(new ExamenEnqueteExport(), $fileName, 'examensenquetes');

        return response()->json([
            "message" => "Exportation des données effectuée avec succès",
            "filename" => $fileName,
            "url" => Storage::disk('examensenquetes')->url($fileName)
        ]);
    }


}
