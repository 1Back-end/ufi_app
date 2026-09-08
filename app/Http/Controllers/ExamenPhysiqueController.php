<?php

namespace App\Http\Controllers;

use App\Exports\DossierConsultationExport;
use App\Exports\ExamenPhysiqueExport;
use App\Models\OpsTbl_Examen_Physique;
use App\Models\OpsTbl_Motif_consultation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;


/**
 * @permission_category Gestion des examens physiques
 * @permission_module Gestion des prestations
 */
class ExamenPhysiqueController extends Controller
{

    /**
     * Display a listing of the resource.
     * @permission ExamenPhysiqueController::index
     * @permission_desc Afficher la liste des examens physiques
     */

    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = OpsTbl_Examen_Physique::with([
                'creator:id,login',
                'updater:id,login',
                'categorieExamenPhysique',
                'dossierConsultation',
            ]);


        if ($request->filled('dossier_consultation_id')) {
            $query->where('dossier_consultation_id', $request->dossier_consultation_id);
        }


        if ($request->filled('categorie_examen_physique_id')) {
            $query->where('categorie_examen_physique_id', $request->categorie_examen_physique_id);
        }


        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%$search%")
                    ->orWhere('libelle', 'like', "%$search%")
                    ->orWhere('resultat', 'like', "%$search%")
                    ->orWhereHas('categorieExamenPhysique', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%$search%");
                    })
                    ->orWhereHas('dossierConsultation', function ($q2) use ($search) {
                        $q2->where('code', 'like', "%$search%");
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
     * @permission ExamenPhysiqueController::export
     * @permission_desc Exporter la liste  des examens physiques
     */

    public function export(Request $request){
        $fileName = 'examens-physiques-' . Carbon::now()->format('Y-m-d') . '.xlsx';

        Excel::store(new ExamenPhysiqueExport(), $fileName, 'examensphysiques');

        return response()->json([
            "message" => "Exportation des données effectuée avec succès",
            "filename" => $fileName,
            "url" => Storage::disk('examensphysiques')->url($fileName)
        ]);

    }

    /**
     * Display a listing of the resource.
     * @permission ExamenPhysiqueController::store
     * @permission_desc Enregistrer les examens physiques pour un dossier de consultations
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        $validated = $request->validate([
            'examens' => 'required|array|min:1',
            'examens.*.libelle' => 'required|string|max:255',
            'examens.*.resultat' => 'nullable|string',
            'examens.*.categorie_examen_physique_id' => 'required|exists:config_tbl_categories_examen_physiques,id',
            'examens.*.dossier_consultation_id' => 'required|exists:dossier_consultations,id',
        ]);

        try {
            $created = DB::transaction(function () use ($validated, $auth) {
                $results = [];
                foreach ($validated['examens'] as $examenData) {
                    $examenData['created_by'] = $auth->id;
                    $results[] = OpsTbl_Examen_Physique::create($examenData);
                }
                return $results;
            });

            return response()->json([
                'message' => 'Examens physiques créés avec succès.',
                'data' => $created
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => "Erreur lors de l'enregistrement des examens physiques.",
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Display a listing of the resource.
     * @permission ExamenPhysiqueController::update
     * @permission_desc Modifier les examens physiques pour un dossier de consultations
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();

        $examen = OpsTbl_Examen_Physique::find($id);

        if (!$examen) {
            return response()->json([
                'message' => 'Examen physique non trouvé.'
            ], 404);
        }

        $validated = $request->validate([
            'libelle' => 'sometimes|required|string|max:255',
            'resultat' => 'nullable|string',
            'categorie_examen_physique_id' => 'sometimes|required|exists:config_tbl_categories_examen_physiques,id',
            'dossier_consultation_id' => 'sometimes|required|exists:dossier_consultations,id',
        ]);

        $validated['updated_by'] = $auth->id;

        $examen->update($validated);

        return response()->json([
            'message' => 'Examen physique mis à jour avec succès.',
            'data' => $examen
        ], 200);
    }
    /**
     * Display a listing of the resource.
     * @permission ExamenPhysiqueController::show
     * @permission_desc Afficher les détails des examens physiques
     */
    public function show(string $id){
        $examen_physique = OpsTbl_Examen_Physique::where('is_deleted', false)
            ->where('id', $id)
            ->with([
                'creator:id,login',
                'updater:id,login',
                'categorieExamenPhysique:id,name',
                'motifConsultation:id,libelle,code,description,dossier_consultation_id',
                'motifConsultation.dossierConsultation:id,code'
            ])
            ->first();
        if(!$examen_physique){
            return response()->json([
                'message' => 'Examen physique introuvable.',
            ],404);
        }else{
            return  response()->json([
                'examen_physique' => $examen_physique
            ]);
        }
    }

    //
}
