<?php

namespace App\Http\Controllers;

use App\Exports\AssureurExport;
use App\Exports\AssureurSearchExport;
use App\Imports\ActesImport;
use App\Imports\AssurancesImport;
use Illuminate\Http\Request;
use App\Models\Assureur;
use App\Models\User;
use App\Models\Centre;
use App\Models\Quotation;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel; // Utilisation de la façade Excel
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * @permission_category Gestion des Assureurs
 */
class AssureurController extends Controller

{
    public function listIdName()
    {
        $data = Assureur::select('id', 'nom')->where('is_deleted', false)->get();
        return response()->json([
            'assureur' => $data
        ]);
    }
    public function import(Request $request)
    {

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new AssurancesImport(), $request->file('file'));
            return response()->json(['message' => 'Importation réussie.']);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Erreur : ' . $e->getMessage()], 500);
        }
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
        $perPage = $request->input('limit', 25);  // Par défaut, 10 éléments par page
        $page = $request->input('page', 1);  // Page courante
        $search = $request->input('search');

        $assureurs = Assureur::where('is_deleted', false)
            ->with(['actes', 'hospitalisations', 'consultations', 'soins', 'createdBy'])
            ->where('is_deleted', false)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('ref', 'like', "%$search%")
                        ->orWhere('nom', 'like', "%$search%")
                        ->orWhere('nom_abrege', 'like', "%$search%")
                        ->orWhere('adresse', 'like', "%$search%")
                        ->orWhere('tel', 'like', "%$search%")
                        ->orWhere('tel1', 'like', "%$search%")
                        ->orWhere('code_quotation', 'like', "%$search%")
                        ->orWhere('Reg_com', 'like', "%$search%")
                        ->orWhere('num_com', 'like', "%$search%")
                        ->orWhere('bp', 'like', "%$search%")
                        ->orWhere('fax', 'like', "%$search%")
                        ->orWhere('code_type', 'like', "%$search%")
                        ->orWhere('code_main', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%")
                        ->orWhere('status', 'like', "%$search%")
                        ->orWhere('ref_assur_principal', 'like', "%$search%");
                });
            })
            ->latest()
            ->paginate(perPage: $perPage, page: $page);

        return response()->json([
            'data' => $assureurs->items(),
            'current_page' => $assureurs->currentPage(),
            'last_page' => $assureurs->lastPage(),
            'total' => $assureurs->total(),
        ]);
    }
    /**
     * Display a listing of the resource.
     * @permission AssureurController::show
     * @permission_desc Afficher les détails d'un assureur
     */
    public function show($id)
    {
        $assureur = Assureur::with([
            'quotation',
            'assureurPrincipal'
        ])
            ->where('id', $id)
            ->where('is_deleted', false)
            ->first();

        if (!$assureur) {
            return response()->json(['message' => 'Assureur introuvable'], 404);
        }

        return response()->json($assureur);
    }
    /**
     * Display a listing of the resource.
     * @permission AssureurController::store
     * @permission_desc Enregistrer des assureurs
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        try {
            // Valider les données du formulaire
            $data = $request->validate([
                'nom' => 'required|string',
                'nom_abrege' => 'nullable|string',
                'adresse' => 'required|string',
                'tel' => 'required|string|unique:assureurs,tel',
                'tel1' => 'nullable|string',
                'code_quotation' => 'required|exists:quotations,id',
                'Reg_com' => 'required|string|unique:assureurs,Reg_com',
                'num_com' => 'required|string|unique:assureurs,num_com',
                'bp' => 'nullable|string',
                'fax' => 'required|string',
                'code_type' => 'required|string|in:Principale,Auxiliaire',
                'code_main' => 'nullable|string',
                'email' => 'nullable|email|unique:assureurs,email',
                'BM' => 'nullable|in:1,0',
                'taux_retenu' => 'required|numeric|min:0|max:100',
                'number_facture' => 'nullable|string|unique:assureurs,number_facture',
                'is_checked' => 'nullable|boolean',
            ]);

            // Gestion du type Principale
            if ($data['code_type'] === 'Principale' && isset($data['ref_assur_principal'])) {
                return response()->json(['message' => 'Un assureur principal ne peut pas avoir de référence à un autre assureur.'], 400);
            }

            // Gestion du type Auxiliaire
            if ($data['code_type'] === 'Auxiliaire') {
                if (empty($data['code_main'])) {
                    return response()->json(['message' => 'Le code de l’assureur principal est requis pour un assureur auxiliaire.'], 400);
                }

                $assureurPrincipal = Assureur::where('ref', $data['code_main'])
                    ->where('code_type', 'Principale')
                    ->first();

                if (!$assureurPrincipal) {
                    return response()->json(['message' => 'Assureur principal introuvable pour le code fourni.'], 400);
                }

                $data['ref_assur_principal'] = $assureurPrincipal->id;
            }

            // Générer un code unique
            $data['ref'] = 'ASS' . now()->format('ymdHis') . mt_rand(10, 99);
            $data['created_by'] = $auth->id;

            // Créer l'assureur
            $assureur = Assureur::create($data);

            return response()->json([
                'message' => 'Assureur créé avec succès.',
                'assureur' => $assureur
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Afficher les erreurs exactes
            return response()->json([
                'message' => 'Données invalides, veuillez vérifier le formulaire.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Impossible de créer l’assureur pour le moment.',
                'error' => $e->getMessage()
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
        $auth = auth()->user();
        $assureur = Assureur::findOrFail($id);

        try {
            // Valider les données de base
            $data = $request->validate([
                'nom' => 'required|string',
                'nom_abrege' => 'nullable|string',
                'adresse' => 'required|string',
                'tel' => 'nullable|string|unique:assureurs,tel,'.$assureur->id,
                'tel1' => 'nullable|string|unique:assureurs,tel,'.$assureur->id,
                'code_quotation' => 'required|exists:quotations,id',
                'Reg_com' => 'required|string|unique:assureurs,Reg_com,' . $assureur->id,
                'num_com' => 'required|string|unique:assureurs,num_com,' . $assureur->id,
                'bp' => 'nullable|string',
                'fax' => 'required|string',
                'code_type' => 'required|string|in:Principale,Auxiliaire',
                'code_main' => 'nullable|string',
                'email' => 'nullable|email',
                'BM' => 'nullable|in:1,0',
                'taux_retenu' => 'required|numeric|min:0|max:100',
                'number_facture' => 'required|string|unique:assureurs,number_facture,'.$assureur->id,
                'is_checked' => 'nullable|boolean',
            ]);


            // Gestion du type Principale
            if ($data['code_type'] === 'Principale' && isset($data['ref_assur_principal'])) {
                return response()->json(['message' => 'Un assureur principal ne peut pas avoir de référence à un autre assureur.'], 400);
            }

            // Gestion du type Auxiliaire
            if ($data['code_type'] === 'Auxiliaire') {
                if (empty($data['code_main'])) {
                    return response()->json(['message' => 'Le code de l’assureur principal est requis pour un assureur auxiliaire.'], 400);
                }

                $assureurPrincipal = Assureur::where('ref', $data['code_main'])
                    ->where('code_type', 'Principale')
                    ->first();

                if (!$assureurPrincipal) {
                    return response()->json(['message' => 'Assureur principal introuvable pour le code fourni.'], 400);
                }

                $data['ref_assur_principal'] = $assureurPrincipal->id;
            }

            $data['updated_by'] = $auth->id;

            // Mettre à jour l'assureur
            $assureur->update($data);

            return response()->json([
                'message' => 'Assureur mis à jour avec succès.',
                'assureur' => $assureur
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Données invalides, veuillez vérifier le formulaire.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Impossible de mettre à jour l’assureur pour le moment.',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    /**
     * Display a listing of the resource.
     * @permission AssureurController::getAssureursPrincipaux
     * @permission_desc Afficher les references et le nom des assureurs principaux
     */
    public function getAssureursPrincipaux(Request $request)
    {
        try {
            $search = $request->query('search', '');
            $status = $request->query('status', 'Actif'); // Actif | Inactif | All

            $query = Assureur::where('code_type', 'Principale')
                ->where('is_deleted', false);

            // Filtre par status
            if (strtolower($status) !== 'all') {
                $query->where('status', $status);
            }

            // Filtre par recherche
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('nom', 'LIKE', "%{$search}%")
                        ->orWhere('ref', 'LIKE', "%{$search}%");
                });
            }

            $assureursPrincipaux = $query
                ->orderBy('created_at', 'desc')
                ->get(['nom', 'ref']);

            return response()->json([
                'message' => 'Assureurs principaux récupérés avec succès.',
                'assureurs_principals' => $assureursPrincipaux
            ], 200);

        } catch (\Exception $e) {

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
    public function getHospitalisations($id)
    {
        $assureur = Assureur::find($id);
        if (!$assureur) {
            return response()->json(['message' => 'Assureur non trouvé'], 404);
        }
        // Retourner uniquement les données nécessaires
        $hospitalisations = $assureur->hospitalisations->map(function ($hospitalisation) {
            return [
                'name' => $hospitalisation->name,
                'pu_default' => $hospitalisation->pu_default,
                'pu' => $hospitalisation->pivot->pu,
            ];
        });

        return response()->json($hospitalisations);
    }
}
