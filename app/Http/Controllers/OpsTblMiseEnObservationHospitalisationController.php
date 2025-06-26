<?php

namespace App\Http\Controllers;

use App\Models\OpsTblMiseEnObservationHospitalisation;
use Illuminate\Http\Request;

class OpsTblMiseEnObservationHospitalisationController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission OpsTblMiseEnObservationHospitalisationController::index
     * @permission_desc Afficher la liste des mises en observation
     */

    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = OpsTblMiseEnObservationHospitalisation::where('is_deleted', false)
            ->with(['rapportConsultation', 'creator', 'updater', 'infirmiere']);

        // Filtrer par rapport_consultation_id
        if ($request->filled('rapport_consultation_id')) {
            $query->where('rapport_consultation_id', $request->input('rapport_consultation_id'));
        }

        // Filtrer par infirmiere_id
        if ($request->filled('infirmiere_id')) {
            $query->where('infirmiere_id', $request->input('infirmiere_id'));
        }

        // Recherche globale
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('observation', 'like', "%$search%")
                    ->orWhere('resume', 'like', "%$search%")
                    ->orWhere('nbre_jours', 'like', "%$search%")
                    ->orWhereHas('infirmiere', function ($q2) use ($search) {
                        $q2->where('nom', 'like', "%$search%")
                            ->orWhere('prenom', 'like', "%$search%")
                            ->orWhere('adresse', 'like', "%$search%")
                            ->orWhere('telephone', 'like', "%$search%")
                            ->orWhere('matricule', 'like', "%$search%")
                            ->orWhere('specialite', 'like', "%$search%");
                    })
                    ->orWhereHas('rapportConsultation', function ($q2) use ($search) {
                        $q2->where('code', 'like', "%$search%")
                            ->orWhere('conclusion', 'like', "%$search%")
                            ->orWhere('recommandations', 'like', "%$search%");
                    });
            });
        }

        $result = $query->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $result->items(),
            'current_page' => $result->currentPage(),
            'last_page' => $result->lastPage(),
            'total' => $result->total(),
            'message' => 'Liste des observations récupérée avec succès.',
        ]);
    }


    /**
     * Display a listing of the resource.
     * @permission OpsTblMiseEnObservationHospitalisationController::historiqueByRapport
     * @permission_desc Afficher la liste des mises en observation pour un rapport de consultation
     */
    public function historiqueByRapport(Request $request, $rapport_consultation_id)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = OpsTblMiseEnObservationHospitalisation::where('is_deleted', false)
            ->where('rapport_consultation_id', $rapport_consultation_id) // Filtre obligatoire
            ->with(['rapportConsultation', 'creator', 'updater', 'infirmiere']);

        // Filtrer par infirmiere_id
        if ($request->filled('infirmiere_id')) {
            $query->where('infirmiere_id', $request->input('infirmiere_id'));
        }

        // Recherche globale
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('observation', 'like', "%$search%")
                    ->orWhere('resume', 'like', "%$search%")
                    ->orWhere('nbre_jours', 'like', "%$search%")
                    ->orWhereHas('infirmiere', function ($q2) use ($search) {
                        $q2->where('nom', 'like', "%$search%")
                            ->orWhere('prenom', 'like', "%$search%")
                            ->orWhere('adresse', 'like', "%$search%")
                            ->orWhere('telephone', 'like', "%$search%")
                            ->orWhere('matricule', 'like', "%$search%")
                            ->orWhere('specialite', 'like', "%$search%");
                    })
                    ->orWhereHas('rapportConsultation', function ($q2) use ($search) {
                        $q2->where('code', 'like', "%$search%")
                            ->orWhere('conclusion', 'like', "%$search%")
                            ->orWhere('recommandations', 'like', "%$search%");
                    });
            });
        }

        $result = $query->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $result->items(),
            'current_page' => $result->currentPage(),
            'last_page' => $result->lastPage(),
            'total' => $result->total(),
            'message' => 'Historique des mises en observation récupéré avec succès.',
        ]);
    }


    /**
     * Display a listing of the resource.
     * @permission OpsTblMiseEnObservationHospitalisationController::show
     * @permission_desc Afficher les détails des mises en observation
     */
    public function show($id){
        $data = OpsTblMiseEnObservationHospitalisation::where('is_deleted', false)
            ->with(['rapportConsultation', 'creator', 'updater','infirmiere'])
            ->findOrFail($id);

        return response()->json([
            'data' => $data,
            'message' => 'Détails de la mise en observation récupérés avec succès.'
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission OpsTblMiseEnObservationHospitalisationController::store
     * @permission_desc Création des mises en observation
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        $messages = [
            'observation.string' => 'L\'observation doit être une chaîne de caractères.',
            'resume.string' => 'Le résumé doit être une chaîne de caractères.',
            'nbre_jours.integer' => 'Le nombre de jours doit être un entier.',
            'rapport_consultation_id.exists' => 'Le rapport de consultation sélectionné est invalide.',
            'infirmiere_id.exists' => 'L\'infirmier(ère) sélectionné(e) est invalide.',
        ];

        $validated = $request->validate([
            'observation' => 'nullable|string',
            'resume' => 'nullable|string',
            'nbre_jours' => 'nullable|integer',
            'rapport_consultation_id' => 'required|exists:ops_tbl_rapport_consultations,id',
            'infirmiere_id' => 'required|exists:nurses,id',
        ], $messages);

        $validated['created_by'] = $auth->id;

        $result = OpsTblMiseEnObservationHospitalisation::create($validated);
        $result->load(['rapportConsultation', 'creator', 'updater', 'infirmiere']);

        return response()->json([
            'data' => $result,
            'message' => 'Observation créée avec succès.',
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission OpsTblMiseEnObservationHospitalisationController::update
     * @permission_desc Modification des mises en observation
     */

    public function update(Request $request, $id)
    {
        $record = OpsTblMiseEnObservationHospitalisation::find($id);

        if (!$record) {
            return response()->json(['message' => 'Observation non trouvée.'], 404);
        }

        $messages = [
            'observation.string' => 'L\'observation doit être une chaîne de caractères.',
            'resume.string' => 'Le résumé doit être une chaîne de caractères.',
            'nbre_jours.integer' => 'Le nombre de jours doit être un entier.',
            'rapport_consultation_id.exists' => 'Le rapport de consultation sélectionné est invalide.',
            'infirmiere_id.exists' => 'L\'infirmier(ère) sélectionné(e) est invalide.',
        ];

        $validated = $request->validate([
            'observation' => 'nullable|string',
            'resume' => 'nullable|string',
            'nbre_jours' => 'nullable|integer',
            'rapport_consultation_id' => 'nullable|exists:ops_tbl_rapport_consultations,id',
            'infirmiere_id' => 'nullable|exists:nurses,id',
        ], $messages);

        $validated['updated_by'] = auth()->id();

        $record->update($validated);
        $record->load(['rapportConsultation', 'creator', 'updater', 'infirmiere']);

        return response()->json([
            'data' => $record,
            'message' => 'Observation mise à jour avec succès.',
        ]);
    }


    /**
     * Display a listing of the resource.
     * @permission OpsTblMiseEnObservationHospitalisationController::destroy
     * @permission_desc Suppression des mises en observation
     */
    public function destroy($id)
    {
        $record = OpsTblMiseEnObservationHospitalisation::find($id);

        if (!$record) {
            return response()->json(['message' => 'Observation non trouvée.'], 404);
        }

        $record->is_deleted = true;
        $record->updated_by = auth()->id();
        $record->save();

        return response()->json([
            'message' => 'Observation supprimée avec succès.',
        ]);
    }



}
