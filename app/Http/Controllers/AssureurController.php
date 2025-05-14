<?php

namespace App\Http\Controllers;

use App\Exports\AssureurExport;
use App\Exports\AssureurSearchExport;
use Illuminate\Http\Request;
use App\Models\Assureur;
use App\Models\User;
use App\Models\Centre;
use App\Models\Quotation;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel; // Utilisation de la façade Excel
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class AssureurController extends Controller
{
    public function listIdName()
    {
        $data = Assureur::select('id', 'nom')->where('is_deleted', false)->get();
        return response()->json([
            'assureur' => $data
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission AssureurController::searchAndExport
     * @permission_desc Filtrer et exporter les données des assureurs
     */
    public function searchAndExport(Request $request)
    {
        // Validation du paramètre de recherche
        $request->validate([
            'query' => 'nullable|string|max:255',
        ]);

        // Récupérer la requête de recherche (le texte de recherche)
        // Récupérer la requête de recherche (le texte de recherche)
        // Récupérer la requête de recherche
        $searchQuery = $request->input('query', '');

        // Initialiser la requête pour récupérer les assureurs
        $query = Assureur::where('is_deleted', false);

        // Si une recherche est effectuée, filtrer les assureurs en fonction des champs spécifiés
        if ($searchQuery) {
            $query->where(function ($query) use ($searchQuery) {
                $query->where('nom', 'like', '%' . $searchQuery . '%')
                    ->orWhere('nom_abrege', 'like', '%' . $searchQuery . '%')
                    ->orWhere('adresse', 'like', '%' . $searchQuery . '%')
                    ->orWhere('tel', 'like', '%' . $searchQuery . '%')
                    ->orWhere('email', 'like', '%' . $searchQuery . '%');
            });
        }

        // Récupérer les assureurs filtrés
        $assureurs = $query->get();

        // Si aucun assureur n'est trouvé
        if ($assureurs->isEmpty()) {
            return response()->json([
                'message' => 'Aucun assureur trouvé pour cette recherche.',
                'data' => []
            ]);
        }

        // Préparer le nom du fichier d'export
        $fileName = 'assureurs-recherche-' . Carbon::now()->format('Y-m-d') . '.xlsx';

        // Exporter les assureurs filtrés par la recherche dans un fichier Excel
        Excel::store(new AssureurSearchExport($assureurs), $fileName, 'exportassureurs');  // Appel de l'export de recherche

        // Retourner la réponse avec le nom du fichier et l'URL de téléchargement
        return response()->json([
            "data" => $assureurs,
            "message" => "Exportation des données de recherche effectuée avec succès",
            "filename" => $fileName,
            "url" => Storage::disk('exportassureurs')->url($fileName) // Retourner l'URL du fichier exporté
        ]);
    }


    /**
     * Display a listing of the resource.
     * @permission AssureurController::export
     * @permission_desc Exporter les données des assureurs
     */
    public function export()
    {
        $fileName = 'assureurs-' . Carbon::now()->format('Y-m-d') . '.xlsx';

        Excel::store(new AssureurExport(), $fileName, 'exportassureurs');

        return response()->json([
            "message" => "Exportation des données effectuée avec succès",
            "filename" => $fileName,
            "url" => Storage::disk('exportassureurs')->url($fileName)
        ]);
    }
    /**
     * Display a listing of the resource.
     * @permission AssureurController::index
     * @permission_desc Afficher  les données des assureurs avec la pagination
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 10);  // Par défaut, 10 éléments par page
        $page = $request->input('page', 1);  // Page courante

        // Récupérer les assureurs avec pagination
        $assureurs = Assureur::where('is_deleted', false)
            ->when($request->input('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where('nom', 'like', '%' . $search . '%')
                    ->orWhere('nom_abrege', 'like', '%' . $search . '%')
                    ->orWhere('adresse', 'like', '%' . $search . '%')
                    ->orWhere('tel', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            })
            ->paginate(perPage: $perPage, page: $page);

        return response()->json([
            'data' => $assureurs->items(),
            'current_page' => $assureurs->currentPage(),  // Page courante
            'last_page' => $assureurs->lastPage(),  // Dernière page
            'total' => $assureurs->total(),  // Nombre total d'éléments
        ]);
    }
    /**
     * Display a listing of the resource.
     * @permission AssureurController::show
     * @permission_desc Afficher les détails d'un assureur
     */
    public function show($id)
    {
        $assureur = Assureur::where('id', $id)->where('is_deleted', false)
            ->with([
                'quotation:id,code'
            ])
            ->first();
        if (!$assureur) {
            return response()->json(['message' => 'Assureur Introuvable'], 404);
        } else {
            return response()->json($assureur, 200);
        }
    }
    /**
     * Display a listing of the resource.
     * @permission AssureurController::store
     * @permission_desc Enregistrer des assureurs
     */
    public function store(Request $request)
    {
        // Vérifie si l'utilisateur est authentifié
        $auth = auth()->user();
        // Vérifie si un ID de centre est envoyé dans le header
        $centreId = $request->header('centre');
        if (!$centreId) {
            return response()->json([
                'message' => __("Vous devez vous connecter à un centre !")
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Recherche du centre
        $centre = Centre::find($centreId);
        if (!$centre) {
            return response()->json([
                'message' => __("Centre introuvable.")
            ], Response::HTTP_NOT_FOUND);
        }


        try {
            // Valider les données du formulaire
            $data = $request->validate([
                'nom' => 'required|string',
                'nom_abrege' => 'nullable|string',
                'adresse' => 'required|string',
                'tel' => 'required|string|unique:assureurs,tel',
                'tel1' => 'required|string|unique:assureurs,tel1',
                'code_quotation' => 'required|exists:quotations,id',
                'Reg_com' => 'required|string|unique:assureurs,Reg_com',
                'num_com' => 'required|string|unique:assureurs,num_com',
                'bp' => 'nullable|string',
                'fax' => 'required|string',
                'code_type' => 'required|string|in:Principale,Auxiliaire',
                'code_main' => 'nullable|string',
                'email' => 'required|email|unique:assureurs,email',
                'BM' => 'nullable|in:1,0',
            ]);

            // Vérifie que si le type est "Principale", alors ref_assur_principal doit être vide
            if ($data['code_type'] == 'Principale' && isset($data['ref_assur_principal'])) {
                return response()->json(['message' => 'Un assureur principale ne doit pas avoir de référence d\'assureur principal.'], 400);
            }
            // Si le type est "Auxiliaire", vérifie que le code_main correspond à un assureur principal existant
            if ($data['code_type'] == 'Auxiliaire') {
                // Vérifie que le code_main existe et qu'il correspond à un assureur de type "Principale"
                if (!isset($data['code_main']) || empty($data['code_main'])) {
                    return response()->json(['message' => 'Le code_main doit être défini pour un assureur auxiliaire.'], 400);
                }

                $assureurPrincipal = Assureur::where('ref', $data['code_main'])->where('code_type', 'Principale')->first();

                if (!$assureurPrincipal) {
                    return response()->json(['message' => 'Le code_main spécifié ne correspond à aucun assureur principal.'], 400);
                }

                // Maintenant on assigne l'ID de l'assureur principal à ref_assur_principal
                $data['ref_assur_principal'] = $assureurPrincipal->id;
            }
            // Générer un code unique
            $data['ref'] = 'ASS' . now()->format('ymdHis') . mt_rand(10, 99);
            $data['created_by'] = $auth->id;
            $data['code_centre'] = $centre->id;

            // Créer l'assureur
            $assureur = Assureur::create($data);

            return response()->json([
                'message' => 'Assureur créé avec succès',
                'assureur' => $assureur
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Erreur de validation',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission AssureurController::update
     * @permission_desc Modifier les informations des assureurs
     */
    public function update(Request $request, $id)
    {
        // Vérifie si l'utilisateur est authentifié
        $auth = auth()->user();

        $centreId = $request->header('centre');
        if (!$centreId) {
            return response()->json([
                'message' => __("Vous devez vous connecter à un centre !")
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Recherche du centre
        $centre = Centre::find($centreId);
        if (!$centre) {
            return response()->json([
                'message' => __("Centre introuvable.")
            ], Response::HTTP_NOT_FOUND);
        }

        // Récupère l'assureur à modifier
        $assureur = Assureur::findOrFail($id);

        try {
            // Valider les données du formulaire
            $data = $request->validate([
                'nom' => 'required|string',
                'nom_abrege' => 'nullable|string',
                'adresse' => 'required|string',
                'tel' => 'required|string|unique:assureurs,tel,' . $assureur->id,
                'tel1' => 'required|string|unique:assureurs,tel1,' . $assureur->id,
                'code_quotation' => 'required|exists:quotations,id',
                'Reg_com' => 'required|string|unique:assureurs,Reg_com,' . $assureur->id,
                'num_com' => 'required|string|unique:assureurs,num_com,' . $assureur->id,
                'bp' => 'nullable|string',
                'fax' => 'required|string',
                'code_type' => 'required|string|in:Principale,Auxiliaire',
                'code_main' => 'nullable|string',
                'email' => 'required|email|unique:assureurs,email,' . $assureur->id,
                'BM' => 'nullable|in:1,0',
            ]);

            // Gestion de la relation principal/auxiliaire
            if ($data['code_type'] == 'Principale') {
                $data['ref_assur_principal'] = null;
            } elseif ($data['code_type'] == 'Auxiliaire') {
                if (!isset($data['code_main']) || empty($data['code_main'])) {
                    return response()->json(['message' => 'Le code_main doit être défini pour un assureur auxiliaire.'], 400);
                }

                $assureurPrincipal = Assureur::where('ref', $data['code_main'])->where('code_type', 'Principale')->first();

                if (!$assureurPrincipal) {
                    return response()->json(['message' => 'Le code_main spécifié ne correspond à aucun assureur principal.'], 400);
                }

                $data['ref_assur_principal'] = $assureurPrincipal->id;
            }

            // Mise à jour des champs
            $data['updated_by'] = $auth->id;
            $data['code_centre'] = $centre->id;

            $assureur->update($data);

            return response()->json([
                'message' => 'Assureur mis à jour avec succès',
                'assureur' => $assureur
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Erreur de validation',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Display a listing of the resource.
     * @permission AssureurController::search
     * @permission_desc Rechercher des assureurs
     */
    public function search(Request $request)
    {
        // Validation du paramètre de recherche
        $request->validate([
            'query' => 'nullable|string|max:255',
        ]);

        // Récupérer la requête de recherche
        $searchQuery = $request->input('query', '');

        $query = Assureur::where('is_deleted', false);

        if ($searchQuery) {
            $query->where(function ($query) use ($searchQuery) {
                $query->where('nom', 'like', '%' . $searchQuery . '%')
                    ->orWhere('nom_abrege', 'like', '%' . $searchQuery . '%')
                    ->orWhere('adresse', 'like', '%' . $searchQuery . '%')
                    ->orWhere('tel', 'like', '%' . $searchQuery . '%')
                    ->orWhere('email', 'like', '%' . $searchQuery . '%');
            });
        }

        $assureurs = $query->get();  // Utilise get() pour obtenir tous les résultats correspondants

        return response()->json([
            'data' => $assureurs,
        ]);
    }





    /**
     * Display a listing of the resource.
     * @permission AssureurController::getAssureursPrincipaux
     * @permission_desc Afficher les references et le nom des assureurs principaux
     */
    public function getAssureursPrincipaux()
    {
        try {
            // Récupérer uniquement les assureurs principaux non supprimés
            $assureursPrincipaux = Assureur::where('code_type', 'Principale') // 'Principal' au lieu de 'Principale' pour correspondre à la terminologie
                ->where('is_deleted', false) // Filtrer les assureurs non supprimés
                ->orderBy('created_at', 'desc') // Optionnel: Trier par date de création décroissante
                ->get(['nom', 'ref']); // Récupérer uniquement le nom et le code

            // Vérifier si des assureurs ont été trouvés
            if ($assureursPrincipaux->isEmpty()) {
                return response()->json([
                    'message' => 'Aucun assureur principal trouvé.'
                ], 404);
            }

            // Retourner les assureurs trouvés en format JSON
            return response()->json([
                'message' => 'Assureurs principaux récupérés avec succès.',
                'assureurs' => $assureursPrincipaux
            ], 200);
        } catch (\Exception $e) {
            // Gestion des erreurs
            return response()->json([
                'error' => 'Une erreur est survenue lors de la récupération des assureurs principaux.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Display a listing of the resource.
     * @permission AssureurController::delete
     * @permission_desc Supprimer un assureur
     */
    public function delete($id)
    {
        // Vérifier si l'assureur existe
        $assureur = Assureur::where('id', $id)->where('is_deleted', false)->first();
        if (!$assureur) {
            return response()->json(['message' => 'Assureur introuvable ou déjà supprimé'], 404);
        }

        try {
            // Marquer l'assureur comme supprimé (soft delete)
            $assureur->update(['is_deleted' => true]);

            return response()->json([
                'message' => 'Assureur supprimé avec succès'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Display a listing of the resource.
     * @permission AssureurController::updateStatus
     * @permission_desc Changer le statut d'un assureur
     */
    public function updateStatus(Request $request, $id, $status)
    {
        // Find the assureur by ID
        $assureur = Assureur::find($id);
        if (!$assureur) {
            return response()->json(['message' => 'Assureur non trouvé'], 404);
        }

        // Check if the assureur is deleted
        if ($assureur->is_deleted) {
            return response()->json(['message' => 'Impossible de mettre à jour un assureur supprimé'], 400);
        }

        // Validate the status
        if (!in_array($status, ['Actif', 'Inactif'])) {
            return response()->json(['message' => 'Statut invalide'], 400);
        }

        // Update the status
        $assureur->status = $status;  // Ensure the correct field name
        $assureur->save();

        // Return the updated assureur
        return response()->json([
            'message' => 'Statut mis à jour avec succès',
            'assureur' => $assureur  // Corrected to $assureur
        ], 200);
    }
    public function getQuotationCode($id)
    {
        $assureur = Assureur::with('quotation')->find($id);

        if (!$assureur) {
            return response()->json(['message' => 'Assureur non trouvé'], 404);
        }

        return response()->json([
            'quotation_id' => $assureur->quotation?->id, // 👈 ajouter l'ID
            'quotation_taux' => $assureur->quotation?->taux, // 👈 toujours garder le taux
        ]);
    }
}
