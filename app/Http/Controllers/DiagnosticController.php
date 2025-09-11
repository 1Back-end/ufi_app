<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Diagnostic;
use Illuminate\Http\Request;

/**
 * @permission_category Gestion des diagnostics
 */
class DiagnosticController extends Controller
{

    public function index(Request $request)
    {
        try {
            $perPage = $request->input('limit', 25);
            $page = $request->input('page', 1);

            $query = Diagnostic::with([
                'typeDiagnostic',
                'categories.sousCategories.maladies'
            ])
                ->latest();

            $results = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $results->items(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'total' => $results->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission DiagnosticController::historiqueDiagnostics
     * @permission_desc Afficher l'historique  des diagnostics d'un client
     */

    public function historiqueDiagnostics(Request $request, $client_id)
    {
        try {
            $perPage = $request->input('limit', 25);
            $page = $request->input('page', 1);

            // Vérifier si le client existe
            $client = Client::find($client_id);
            if (!$client) {
                return response()->json(['message' => 'Client non trouvé'], 404);
            }

            // Récupérer les diagnostics liés au client via la relation des rendez-vous
            $diagnostics = Diagnostic::whereHas('rapportConsultation.dossierConsultation.rendezVous', function ($query) use ($client_id) {
                $query->where('client_id', $client_id);
            })
                ->with([
                    'typeDiagnostic',
                    'categories.sousCategories.maladies',
                    'rapportConsultation.dossierConsultation.rendezVous.client',
                    'rapportConsultation.dossierConsultation.rendezVous.consultant',
                ])
                ->latest()
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $diagnostics->items(),
                'current_page' => $diagnostics->currentPage(),
                'last_page' => $diagnostics->lastPage(),
                'total' => $diagnostics->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue : ' . $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Display a listing of the resource.
     * @permission DiagnosticController::store
     * @permission_desc Création des diagnostics des dossiers de consultations
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        try {
            $validated = $request->validate([
                'rapport_consultations_id' => 'nullable|exists:ops_tbl_rapport_consultations,id',
                'type_diagnostic_id' => 'nullable|exists:configtbl_type_diagnostic,id',
                'description' => 'nullable|string',

                // Catégories liées
                'categorie_diagnostic_ids' => 'nullable|array',
                'categorie_diagnostic_ids.*' => 'exists:categorie_diagnostic,id',

                // Sous-catégories liées (facultatif - à prévoir)
                'sous_categorie_ids' => 'nullable|array',
                'sous_categorie_ids.*' => 'exists:config_sous_categorie_diagnostic,id',

                // Maladies liées (facultatif - à prévoir)
                'maladie_ids' => 'nullable|array',
                'maladie_ids.*' => 'exists:config_tbl_maladie_diagnostic,id',
            ]);

            // Création du diagnostic
            $diagnostic = Diagnostic::create([
                'rapport_consultations_id' => $validated['rapport_consultations_id'] ?? null,
                'type_diagnostic_id' => $validated['type_diagnostic_id'] ?? null,
                'description' => $validated['description'] ?? null,
                'created_by' => $auth->id,
            ]);

            // Attache les catégories (via pivot)
            if (!empty($validated['categorie_diagnostic_ids'])) {
                $diagnostic->categories()->attach($validated['categorie_diagnostic_ids']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Diagnostic enregistré avec succès.',
                'data' => $diagnostic->load('categories.sousCategories.maladies'),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue : ' . $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Display a listing of the resource.
     * @permission DiagnosticController::update
     * @permission_desc Modification des diagnostics des dossiers de consultations
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();

        $request->validate([
            'rapport_consultations_id' => 'nullable|exists:ops_tbl_rapport_consultations,id',
            'type_diagnostic_id' => 'nullable|exists:configtbl_type_diagnostic,id',
            'description' => 'nullable|string',

        ]);

        $diagnostic = Diagnostic::findOrFail($id);

        $diagnostic->update([
            'code' => $request->code,
            'rapport_consultations_id' => $request->rapport_consultations_id,
            'type_diagnostic_id' => $request->type_diagnostic_id,
            'description'  => $request->description,
            'updated_by' => $auth->id,
        ]);

        return response()->json([
            'message' => 'Diagnostic mis à jour avec succès.',
            'data' => $diagnostic
        ], 200);
    }

    public function show($id)
    {
        try {
            $diagnostic = Diagnostic::with([
                'typeDiagnostic',
                'categories.sousCategories.maladies'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'diagnostic' => $diagnostic
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => "Diagnostic avec l'id {$id} introuvable."
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue : ' . $e->getMessage()
            ], 500);
        }
    }


    //
}
