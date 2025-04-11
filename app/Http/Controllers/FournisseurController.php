<?php

namespace App\Http\Controllers;
use App\Models\Fournisseurs ;
use App\Models\User;
use Illuminate\Http\Request;

class FournisseurController extends Controller
{
    public function index(Request $request){
        $perPage = $request->input('limit', 10);  // Par défaut, 10 éléments par page
        $page = $request->input('page', 1);  // Page courante

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

    public function show($id){
        $fournisseur = Fournisseurs::where('id', $id)->where('is_deleted', false)->first();
        if(!$fournisseur){
            return response()->json(['message' => 'Fournisseur not found'], 404);
        }else{
            return response()->json($fournisseur,200);
        }

}

    public function store(Request $request)
    {
        // Authentifier l'utilisateur
        $auth = auth()->user();

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
    public function search(Request $request)
    {
        $query = $request->input('query');

        if (!$query) {
            return response()->json([
                'message' => 'Veuillez fournir un terme de recherche.'
            ], 400); // Bad Request
        }

        $fournisseurs = Fournisseurs::where('nom', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->orWhere('tel', 'LIKE', "%{$query}%")
            ->get();

        if ($fournisseurs->isEmpty()) {
            return response()->json([
                'message' => 'Aucun fournisseur trouvé.'
            ], 404);
        }

        return response()->json([
            'fournisseurs' => $fournisseurs
        ], 200);
    }

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
    public function delete($id)
    {
        // Rechercher le fournisseur non supprimé
        $fournisseur = Fournisseurs::where('id', $id)->where('is_deleted', false)->first();

        // Si non trouvé
        if (!$fournisseur) {
            return response()->json(['message' => 'Fournisseur introuvable ou déjà supprimé'], 404);
        }

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




    //
}
