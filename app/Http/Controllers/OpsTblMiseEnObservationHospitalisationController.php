<?php

namespace App\Http\Controllers;

use App\Models\Client;
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
     * @permission OpsTblMiseEnObservationHospitalisationController::historiqueMisesEnObservation
     * @permission_desc Afficher l'historique des mises en observation d'un client
     */
    public function historiqueMisesEnObservation(Request $request, $client_id)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        // Vérifier si le client existe (optionnel, si tu as un modèle Client)
        $client = Client::find($client_id);
        if (!$client) {
            return response()->json(['message' => 'Client non trouvé'], 404);
        }

        // Construire la requête pour récupérer les mises en observation liées au client
        $query = OpsTblMiseEnObservationHospitalisation::where('is_deleted', false)
            ->whereHas('rapportConsultation.dossierConsultation.rendezVous', function ($q) use ($client_id) {
                $q->where('client_id', $client_id);
            })
            ->with([
                'rapportConsultation.dossierConsultation.rendezVous.client',
                'rapportConsultation.dossierConsultation.rendezVous.consultant',
                'rapportConsultation',
                'infirmiere',
                'creator',
                'updater',
                'prescriptionPharmaceutique.products',
            ]);

        // Recherche globale facultative (par exemple dans observation, résumé, ou infirmière)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('observation', 'like', "%$search%")
                    ->orWhere('resume', 'like', "%$search%")
                    ->orWhereHas('infirmiere', function ($q2) use ($search) {
                        $q2->where('nom', 'like', "%$search%")
                            ->orWhere('prenom', 'like', "%$search%");
                    });
            });
        }

        // Pagination et ordre
        $result = $query->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $result->items(),
            'current_page' => $result->currentPage(),
            'last_page' => $result->lastPage(),
            'total' => $result->total(),
            'message' => 'Historique des mises en observation récupéré avec succès.',
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
        try {
            $auth = auth()->user();

            $messages = [
                'observation.string' => 'L\'observation doit être une chaîne de caractères.',
                'resume.string' => 'Le résumé doit être une chaîne de caractères.',
                'nbre_jours.integer' => 'Le nombre de jours doit être un entier.',
                'rapport_consultation_id.exists' => 'Le rapport de consultation sélectionné est invalide.',
                'infirmiere_id.exists' => 'L\'infirmier(ère) sélectionné(e) est invalide.',
                'product_quantities.array' => 'Les produits doivent être un tableau.',
                'product_quantities.*' => 'Chaque quantité doit être un entier supérieur à 0.'
            ];

            $validated = $request->validate([
                'observation' => 'nullable|string',
                'resume' => 'nullable|string',
                'nbre_jours' => 'nullable|integer',
                'rapport_consultation_id' => 'required|exists:ops_tbl_rapport_consultations,id',
                'infirmiere_id' => 'required|exists:nurses,id',
                'product_quantities' => 'nullable|array',
                'product_quantities.*' => 'integer|min:1'
            ], $messages);

            // Vérification supplémentaire : produits existent en base
            if (!empty($validated['product_quantities'])) {
                $productIds = array_keys($validated['product_quantities']);

                // Vérifie que tous les IDs existent dans la table ops_tbl_products
                $count = \App\Models\Product::whereIn('id', $productIds)->count();

                if ($count !== count($productIds)) {
                    return response()->json([
                        'message' => 'Certains produits sélectionnés sont invalides ou inexistants.'
                    ], 422);
                }
            }

            $validated['created_by'] = $auth->id;

            // Création de la mise en observation
            $record = OpsTblMiseEnObservationHospitalisation::create($validated);

            // Création de la prescription si produits présents
            if (!empty($validated['product_quantities'])) {
                $prescription = $record->prescriptionPharmaceutique()->create([
                    'created_by' => $auth->id,
                ]);

                $syncData = [];
                foreach ($validated['product_quantities'] as $productId => $quantite) {
                    $syncData[$productId] = ['quantite' => $quantite];
                }

                $prescription->products()->sync($syncData);
            }

            // Chargement des relations
            $record->load([
                'rapportConsultation',
                'creator',
                'updater',
                'infirmiere',
                'prescriptionPharmaceutique.products'
            ]);

            return response()->json([
                'data' => $record,
                'message' => 'Mise en observation créée avec succès.',
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation échouée.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Une erreur est survenue lors de la création.',
                'error' => $e->getMessage(),
            ], 500);
        }
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
            'product_quantities.array' => 'Les produits doivent être un tableau.',
            'product_quantities.*' => 'Chaque quantité doit être un entier supérieur à 0.'
        ];

        $validated = $request->validate([
            'observation' => 'nullable|string',
            'resume' => 'nullable|string',
            'nbre_jours' => 'nullable|integer',
            'rapport_consultation_id' => 'nullable|exists:ops_tbl_rapport_consultations,id',
            'infirmiere_id' => 'nullable|exists:nurses,id',
            'product_quantities' => 'nullable|array',
            'product_quantities.*' => 'integer|min:1',
        ], $messages);

        $validated['updated_by'] = auth()->id();

        // 🔁 Mise à jour de la mise en observation
        $record->update($validated);

        // 🔁 Gestion de la prescription pharmaceutique (si produits envoyés)
        if (!empty($validated['product_quantities'])) {
            // Vérifie si une prescription existe
            $prescription = $record->prescriptionPharmaceutique;

            if (!$prescription) {
                // Création si elle n'existe pas
                $prescription = $record->prescriptionPharmaceutique()->create([
                    'created_by' => auth()->id(),
                    'mise_en_observation_id' => $record->id,
                ]);
            } else {
                $prescription->update([
                    'updated_by' => auth()->id(),
                ]);
            }

            // Préparation des données de synchronisation
            $syncData = [];
            foreach ($validated['product_quantities'] as $productId => $quantite) {
                $syncData[$productId] = ['quantite' => $quantite];
            }

            // 🔁 Synchroniser les produits avec quantités
            $prescription->products()->sync($syncData);
        }

        // Charger les relations
        $record->load([
            'rapportConsultation',
            'creator',
            'updater',
            'infirmiere',
            'prescriptionPharmaceutique.products'
        ]);

        return response()->json([
            'data' => $record,
            'message' => 'Mise en observation mise à jour avec succès.',
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
            'message' => 'Mise en observation supprimée avec succès.',
        ]);
    }



}
