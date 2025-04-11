<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Category;
class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 10);  // Par défaut, 10 éléments par page
        $page = $request->input('page', 1);  // Page courante

        // Récupérer les assureurs avec pagination
        $categories_produits = Category::where('is_deleted', false)
            ->paginate($perPage);

        return response()->json([
            'data' => $categories_produits->items(),
            'current_page' => $categories_produits->currentPage(),  // Page courante
            'last_page' => $categories_produits->lastPage(),  // Dernière page
            'total' => $categories_produits->total(),  // Nombre total d'éléments
        ]);
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        try {
            // Validation des données d'entrée
            $data = $request->validate([
                'name' => 'required|string|unique:categories,name',  // Nom uniqu
            ]);
            // Ajout de l'ID de l'utilisateur créateur
            $data['created_by'] = $auth->id;
            // Création de l'élément dans la base de données
            $category_produit = Category::create($data);
            // Retourner une réponse JSON avec les données et un message de succès
            return response()->json([
                'data' => $category_produit,
                'message' => 'Enregistrement effectué avec succès'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Erreur de validation
            return response()->json([
                'error' => 'Erreur de validation',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Erreur générale
            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            // Récupérer l'élément par ID ou retourner une erreur 404 si non trouvé
            $category_produits = Category::where('id', $id)
                ->where('is_deleted', false)  // Assurez-vous de vérifier si l'élément n'est pas marqué comme supprimé
                ->firstOrFail();

            // Retourner une réponse JSON avec les détails de la ressource
            return response()->json([
                'data' => $category_produits,
                'message' => 'Détails récupérés avec succès'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Si l'élément n'est pas trouvé, retourner une erreur 404
            return response()->json([
                'error' => 'Ressource non trouvée',
                'message' => 'Aucune ressource trouvée avec cet ID.'
            ], 404);
        } catch (\Exception $e) {
            // Autres erreurs
            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();
        try {
            // Récupérer l'élément à mettre à jour par son ID
            $category_produit = Category::where('is_deleted',false)->first();  // Récupère l'élément ou renvoie une erreur 404

            // Validation des données d'entrée
            $data = $request->validate([
                'name' => 'required|string|unique:categories,name,' . $category_produit->id,  // Vérifie que le nom est unique sauf pour l'élément actue
            ]);
            // Mise à jour des données
            $data['updated_by'] = $auth->id;  // Ajouter l'ID de l'utilisateur qui met à jour
            // Appliquer la mise à jour
            $category_produit->update($data);
            // Retourner une réponse JSON avec les données mises à jour et un message de succès
            return response()->json([
                'data' => $category_produit,
                'message' => 'Mise à jour effectuée avec succès'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Erreur de validation
            return response()->json([
                'error' => 'Erreur de validation',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Erreur générale
            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
