<?php

namespace App\Http\Controllers;

use App\Models\GroupProduct;
use App\Models\Product;
use Illuminate\Http\Request;

use App\Models\Category;
class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission CategoryController::listIdName
     * @permission_desc Afficher l'id et nom de la catégorie des produits
     */
    public function listIdName()
    {
        $categories = Category::select('id', 'name')
            ->where('is_deleted', false)
            ->get();

        return response()->json([
            'categories' => $categories
        ]);
    }
    /**
     * Display a listing of the resource.
     * @permission CategoryController::index
     * @permission_desc Afficher la liste des catégories de produits avec la pagination
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 10);  // Par défaut, 10 éléments par page
        $page = $request->input('page', 1);  // Page courante
        $search = $request->input('search');
        $query = Category::where('is_deleted', false);
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%$search%");
            });
        }

        // Récupérer les assureurs avec pagination
        $categories_produits = Category::where('is_deleted', false)
            ->with('groupProduct:id,name')
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
     * Display a listing of the resource.
     * @permission CategoryController::store
     * @permission_desc Enregistrer les catégories de produits
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        try {
            // Validation des données d'entrée
            $data = $request->validate([
                'name' => 'required|string|unique:categories,name',
                'group_product_id' => 'required|exists:group_products,id',
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
     * Display a listing of the resource.
     * @permission CategoryController::show
     * @permission_desc Afficher les détails d'une catégorie de produits
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
     * Display a listing of the resource.
     * @permission CategoryController::listIdName
     * @permission_desc Mettre à jour une catégorie de produits
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user(); // Récupère l'utilisateur authentifié

        try {
            // Validation des données d'entrée
            $data = $request->validate([
                'name' => 'required|string|unique:categories,name,' . $id, // Exclut l'ID actuel de la validation unique
                'group_product_id' => 'required|exists:group_products,id', // Vérifie que l'ID du groupe produit existe dans la table group_products
                'description' => 'nullable|string',
            ]);

            // Recherche de la catégorie par ID
            $category = Category::findOrFail($id); // Si la catégorie n'existe pas, cela lèvera une exception 404

            // Mise à jour des données de la catégorie
            $data['updated_by'] = $auth->id; // Ajoute l'ID de l'utilisateur ayant effectué la mise à jour

            $category->update($data);

            // Retourner une réponse JSON avec les données et un message de succès
            return response()->json([
                'data' => $category,
                'message' => 'Catégorie mise à jour avec succès',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Erreur de validation
            return response()->json([
                'error' => 'Erreur de validation',
                'details' => $e->errors()
            ], 422); // Code de réponse 422 pour les erreurs de validation
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Catégorie non trouvée
            return response()->json([
                'error' => 'Catégorie non trouvée',
            ], 404); // Code de réponse 404 pour non trouvé
        } catch (\Exception $e) {
            // Erreur générale
            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500); // Code de réponse 500 pour les erreurs internes du serveur
        }
    }


    /**
     * Display a listing of the resource.
     * @permission CategoryController::listIdName
     * @permission_desc Supprimer une catégorie de produits
     */
    public function destroy(string $id)
    {
        $category_product = Category::findOrFail($id);

        // Vérifie s'il est utilisé dans un produit
        $isUsed = Product::where('categories_id', $id)->exists();

        if ($isUsed) {
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de supprimer : cette catégorie de produit est utilisée par au moins un produit.'
            ], 400);
        }

        $category_product->is_deleted = true;
        $category_product->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Suppression éffectué avec succès'
        ]);
    }
}
