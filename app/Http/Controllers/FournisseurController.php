<?php

namespace App\Http\Controllers;
use App\Exports\AssureurExport;
use App\Exports\FournisseurExport;
use App\Exports\FournisseurSearchExport;
use App\Models\Fournisseurs ;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @permission_category Gestion des fournisseurs
 */


class FournisseurController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission FournisseurController::listIdName
     * @permission_desc Afficher l'id et nom des fournisseurs
     */
    public function ListIdName()
    {
        $fournisseurs = Fournisseurs::select('id', 'nom')
            ->where('is_deleted', false)
            ->get();

        return response()->json([
            'fournisseurs' => $fournisseurs
        ]);
    }
    /**
     * Display a listing of the resource.
     * @permission FournisseurController::index
     * @permission_desc Afficher la liste des fournisseurs avec pagination
     */
    public function index(Request $request){
        $perPage = $request->input('limit', 10);  // Par défaut, 10 éléments par page
        $page = $request->input('page', 1);  // Page courante
        $search = $request->input('search');

        $query = Fournisseurs::where('is_deleted', false);

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('email', 'like', "%$search%")
                ->orWhere('adresse', 'like', '%' . $search . '%')
                    ->orWhere('tel', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

// Récupérer les assureurs avec pagination
        $fournisseurs = Fournisseurs::where('is_deleted', false)
            ->paginate($perPage);

        return response()->json([
            'data' => $fournisseurs->items(),
            'current_page' => $fournisseurs->currentPage(),  // Page courante
            'last_page' => $fournisseurs->lastPage(),  // Dernière page
            'total' => $fournisseurs->total(),  // Nombre total d'éléments
        ]);
    }
    /**
     * Display a listing of the resource.
     * @permission FournisseurController::export
     * @permission_desc Exporter les données des fournisseurs
     */
    public function export()
    {
        try {
            // Nom du fichier avec la date actuelle
            $fileName = 'fournisseurs-' . Carbon::now()->format('Y-m-d') . '.xlsx';

            // Stockage du fichier Excel dans le disque 'exportfournisseurs'
            Excel::store(new FournisseurExport(), $fileName, 'exportfournisseurs');

            // Retourner une réponse JSON avec les informations de l'exportation
            return response()->json([
                "message" => "Exportation des données effectuée avec succès",
                "filename" => $fileName,
                "url" => Storage::disk('exportfournisseurs')->url($fileName) // Assurez-vous que le disque est correctement configuré dans config/filesystems.php
            ], 200);
        } catch (\Exception $e) {
            // Si une erreur se produit, retourner une réponse d'erreur
            return response()->json([
                "message" => "Erreur lors de l'exportation des données",
                "error" => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Display a listing of the resource.
     * @permission FournisseurController::searchAndExport
     * @permission_desc Rechercher et exporter les données des fournisseurs
     */
    public function searchAndExport(Request $request)
    {
        // Validation du paramètre de recherche
        $request->validate([
            'query' => 'nullable|string|max:255',
        ]);

        $searchQuery = $request->input('query', '');

        // Initialisation de la requête
        $query = Fournisseurs::where('is_deleted', false);

        // Appliquer les filtres si une requête de recherche est fournie
        if ($searchQuery) {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('nom', 'like', '%' . $searchQuery . '%')
                    ->orWhere('adresse', 'like', '%' . $searchQuery . '%')
                    ->orWhere('tel', 'like', '%' . $searchQuery . '%')
                    ->orWhere('email', 'like', '%' . $searchQuery . '%');
            });
        }

        $fournisseurs = $query->get();

        // Vérifier si la collection est vide
        if ($fournisseurs->isEmpty()) {
            return response()->json([
                'message' => 'Aucun fournisseur trouvé pour cette recherche.',
                'data' => []
            ], 404);
        }

        try {
            // Définir le nom du fichier d'export
            $fileName = 'fournisseurs-recherche-' . Carbon::now()->format('Y-m-d') . '.xlsx';

            // Exporter les données vers un fichier Excel
            Excel::store(new FournisseurSearchExport($fournisseurs), $fileName, 'exportfournisseurs');

            return response()->json([
                'message' => 'Exportation des données effectuée avec succès.',
                'filename' => $fileName,
                'url' => Storage::disk('exportfournisseurs')->url($fileName),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'exportation des données.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Display a listing of the resource.
     * @permission FournisseurController::search
     * @permission_desc Rechercher un fournisseur
     */
    public function search(Request $request)
    {
        // Validation du paramètre de recherche
        $request->validate([
            'query' => 'nullable|string|max:255',
        ]);

        // Récupérer la requête de recherche
        $searchQuery = $request->input('query', '');

        $query = Fournisseurs::where('is_deleted', false);

        if ($searchQuery) {
            $query->where(function($query) use ($searchQuery) {
                $query->where('nom', 'like', '%' . $searchQuery . '%')
                    ->orWhere('adresse', 'like', '%' . $searchQuery . '%')
                    ->orWhere('tel', 'like', '%' . $searchQuery . '%')
                    ->orWhere('email', 'like', '%' . $searchQuery . '%');
            });
        }

        $fournisseurs = $query->get();

        return response()->json([
            'data' => $fournisseurs,
        ]);
    }


    /**
     * Display a listing of the resource.
     * @permission FournisseurController::show
     * @permission_desc Afficher les détails d'un fournisseur
     */
    public function show($id){
        $fournisseur = Fournisseurs::where('id', $id)->where('is_deleted', false)->first();
        if(!$fournisseur){
            return response()->json(['message' => 'Fournisseur not found'], 404);
        }else{
            return response()->json($fournisseur,200);
        }

}

    /**
     * Display a listing of the resource.
     * @permission FournisseurController::store
     * @permission_desc Enregistrer un fournisseur
     */

    public function store(Request $request)
    {
        // Authentifier l'utilisateur
        $auth = auth()->user();
        if(!$auth){
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Validation des données entrantes
        $data = $request->validate([
            'nom' => 'required|string|max:255',
            'adresse' => 'required|string|max:255',
            'tel' => 'required|string|unique:fournisseurs|max:20',
            'fax' => 'required|string|max:20',
            'email' => 'required|email|unique:fournisseurs|max:255',
            'ville' => 'required|string|max:100',
            'pays' => 'required|string|max:100',
            'state' => 'required|string|max:100',
        ]);

        // Ajouter l'ID de l'utilisateur authentifié
        $data['created_by'] = $auth->id;

        // Créer un nouveau fournisseur avec les données validées
        $fournisseur = Fournisseurs::create($data);

        // Retourner la réponse JSON avec un message de succès et les informations du fournisseur créé
        return response()->json([
            'message' => 'Fournisseur créé avec succès',
            'fournisseur' => $fournisseur
        ], 201); // Code de statut HTTP 201 pour une ressource créée
    }
    /**
     * Display a listing of the resource.
     * @permission FournisseurController::search
     * @permission_desc Rechercher des fournisseurs
     */

    /**
     * Display a listing of the resource.
     * @permission FournisseurController::update
     * @permission_desc Modifier un fournisseur
     */

    public function update(Request $request, $id)
    {
        try {
            $auth = auth()->user();

            $fournisseur = Fournisseurs::where('id', $id)->where('is_deleted', false)->first();

            if (!$fournisseur) {
                return response()->json(['message' => 'Fournisseur non trouvé'], 404);
            }

            $data = $request->validate([
                'nom' => 'required|string|max:255',
                'adresse' => 'required|string|max:255',
                'tel' => 'required|string|max:20|unique:fournisseurs,tel,' . $fournisseur->id,
                'fax' => 'required|string|max:20',
                'email' => 'required|email|max:255|unique:fournisseurs,email,' . $fournisseur->id,
                'ville' => 'required|string|max:100',
                'pays' => 'required|string|max:100',
                'state' => 'required|string|max:100',
            ]);

            $data['updated_by'] = $auth->id;

            $fournisseur->update($data);

            return response()->json([
                'message' => 'Fournisseur mis à jour avec succès',
                'fournisseur' => $fournisseur
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Une erreur est survenue',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTrace() : []
            ], 500);
        }
    }
    /**
     * Display a listing of the resource.
     * @permission FournisseurController::delete
     * @permission_desc Supprimer un fournisseur
     */
    public function delete($id)
    {
        // Rechercher le fournisseur non supprimé
        $fournisseur = Fournisseurs::where('id', $id)
            ->where('is_deleted', false)
            ->first();

        // Si non trouvé
        if (!$fournisseur) {
            return response()->json(['message' => 'Fournisseur introuvable ou déjà supprimé'], 404);
        }

//        // Vérifier s'il est utilisé dans la table products
//        $isUsed = Product::where('fournisseur_id', $id)->exists();
//
//        if ($isUsed) {
//            return response()->json([
//                'message' => 'Impossible de supprimer : ce fournisseur est utilisé dans des produits.'
//            ], 400);
//        }

        try {
            // Soft delete : on marque comme supprimé
            $fournisseur->update(['is_deleted' => true]);

            return response()->json([
                'message' => 'Fournisseur supprimé avec succès'
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
     * @permission FournisseurController::updateStatus
     * @permission_desc Changer le statut  d'un fournisseur
     */
    public function updateStatus(Request $request, $id, $status)
    {
        // Find the assureur by ID
        $fournisseur = Fournisseurs::find($id);
        if (!$fournisseur) {
            return response()->json(['message' => 'Fournisseur non trouvé'], 404);
        }

        // Check if the assureur is deleted
        if ($fournisseur->is_deleted) {
            return response()->json(['message' => 'Impossible de mettre à jour un fournisseur supprimé'], 400);
        }

        // Validate the status
        if (!in_array($status, ['Actif', 'Inactif'])) {
            return response()->json(['message' => 'Statut invalide'], 400);
        }

        // Update the status
        $fournisseur->status = $status;  // Ensure the correct field name
        $fournisseur->save();

        // Return the updated assureur
        return response()->json([
            'message' => 'Statut mis à jour avec succès',
            'assureur' => $fournisseur  // Corrected to $assureur
        ], 200);
    }





    //
}
