<?php

namespace App\Http\Controllers;

use App\Exports\ExamenEnqueteExport;
use App\Exports\MotifsExport;
use App\Models\DossierConsultation;
use App\Models\OpsTbl_Examen_Physique;
use App\Models\OpsTblEnquete;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @permission_category Gestion des enquete systémiques
 * @permission_module Gestion des prestations
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

        $query = OpsTblEnquete::with([
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
     * @permission OpsTblEnqueteController::store
     * @permission_desc Enregistrer des enquêtes pour un dossier de consultations
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
        $dossierId = $validated['enquetes'][0]['dossier_consultation_id'];
        $dossier = DossierConsultation::find($dossierId);

        Log::info('Vérification du dossier pour les examens (globaux) :', [
            'dossier_id' => $dossierId,
            'dossier_trouve' => $dossier ? true : false,
            'is_have_antecedent' => $dossier?->is_have_antecedent,
            'dossier_data' => $dossier?->toArray()
        ]);

        if (!$dossier || !$dossier->is_have_antecedent) {
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de continuer : aucun antécédent n\'est associé à ce dossier.'
            ], 422);
        }

        try {
            $created = DB::transaction(function () use ($validated, $auth) {
                $results = [];
                foreach ($validated['enquetes'] as $enqueteData) {
                    $enqueteData['created_by'] = $auth->id;
                    $results[] = OpsTblEnquete::create($enqueteData);
                }
                return $results;
            });

            \App\Models\DossierConsultation::where('id', $dossier->id)
                ->update(['is_have_enquete_systemique' => true]);

            return response()->json([
                'message' => 'Enquêtes enregistrées avec succès.',
                'data' => $created
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => "Erreur lors de l'enregistrement des enquêtes.",
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission OpsTblEnqueteController::show
     * @permission_desc Afficher les détails des enquêtes pour un dossier de consultations
     */
    public function show(string $id)
    {
        $enquete = OpsTblEnquete::with(['categorieEnquete:id,name'])->find($id);

        if (!$enquete) {
            return response()->json([
                'message' => 'Enquête introuvable.'
            ], 404);
        }

        return response()->json([
            'message' => 'Enquête récupérée avec succès.',
            'data' => $enquete
        ], 200);
    }

    /**
     * Display a listing of the resource.
     * @permission OpsTblEnqueteController::update
     * @permission_desc Modifier des enquêtes pour un dossier de consultations
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();

        $enquete = OpsTblEnquete::find($id);

        if (!$enquete) {
            return response()->json([
                'message' => 'Enquête non trouvée.'
            ], 404);
        }

        $validated = $request->validate([
            'libelle' => 'sometimes|required|string|max:255',
            'resultat' => 'nullable|string',
            'categories_enquetes_id' => 'sometimes|required|exists:configtbl_categories_enquetes,id',
            'dossier_consultation_id' => 'sometimes|required|exists:dossier_consultations,id',
        ]);

        $validated['updated_by'] = $auth->id;

        $enquete->update($validated);

        return response()->json([
            'message' => 'Enquête mise à jour avec succès.',
            'data' => $enquete
        ], 200);
    }



}
