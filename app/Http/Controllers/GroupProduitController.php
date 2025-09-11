<?php

namespace App\Http\Controllers;

use App\Models\GroupProduct;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * @permission_category Gestion des groupes de produits
 */
class GroupProduitController extends Controller
{

    public function listIdName()
    {
        $groupProduits = GroupProduct::select('id', 'name')
            ->where('is_deleted', false)
            ->get();

        return response()->json([
            'group_produits' => $groupProduits
        ]);
    }
    public function getCategories($group_product_id)
    {
        // Trouver le groupe de produit par son ID
        $groupProduct = GroupProduct::find($group_product_id);

        // Si le groupe de produit existe
        if ($groupProduct) {
            // Récupérer les catégories associées
            return response()->json([
                'data' => $groupProduct->categories
            ], 200);
        }

        // Si le groupe de produit n'existe pas
        return response()->json([
            'message' => 'Group product not found'
        ], 404);
    }
    /**
     * Display a listing of the resource.
     * @permission GroupProduitController::index
     * @permission_desc Afficher la liste des groupes de produits
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 10);  // Par défaut, 10 éléments par page
        $page = $request->input('page', 1);  // Page courante
        $search = $request->input('search');
        $query = GroupProduct::where('is_deleted', false);
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%$search%");
            });
        }
        // Récupérer les assureurs avec pagination
        $groupe_produits = GroupProduct::where('is_deleted', false)
            ->paginate($perPage);

        return response()->json([
            'data' => $groupe_produits->items(),
            'current_page' => $groupe_produits->currentPage(),  // Page courante
            'last_page' => $groupe_produits->lastPage(),  // Dernière page
            'total' => $groupe_produits->total(),  // Nombre total d'éléments
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
     * @permission GroupProduitController::store
     * @permission_desc Créer un groupe de produit
     */
    public function store(Request $request)
    {
        $auth = auth()->user();
        $data = $request->validate([
            'name' => 'required|string|unique:group_products,name',
        ]);
        $data['created_by'] = $auth->id;
        $unite_produits = GroupProduct::create($data);
        return response()->json([
            'data' => $unite_produits,
            'message'=> 'Enregistrement effectué avec succès'
        ]);
        //
    }

    /**
     * Display the specified resource.
     */
    /**
     * Display a listing of the resource.
     * @permission GroupProduitController::show
     * @permission_desc Afficher les détails d'un groupe produits
     */
    public function show(string $id)
    {
        $groupe_produit = GroupProduct::where('id', $id)->where('is_deleted', false)->first();
        if(!$groupe_produit){
            return response()->json(['message' => 'Le groupe produit n\'existe pas',404]);
        }else{
            return response()->json($groupe_produit);
        }

        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {

    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * Display a listing of the resource.
     * @permission GroupProduitController::update
     * @permission_desc Modifier un groupe produit
     */
    public function update(Request $request, string $id)
    {
        $groupe_produit = GroupProduct::where('id', $id)->where('is_deleted', false)->first();

        // Valider les données reçues
        $data = $request->validate([
            'name' => 'required|string|unique:group_products,name,' . $id,  // Autoriser la modification du nom, mais éviter la duplication
        ]);

        // Mettre à jour les données
        $groupe_produit->update($data);

        // Retourner la réponse avec les données mises à jour
        return response()->json([
            'data' => $groupe_produit,
            'message' => 'Mise à jour effectuée avec succès'
        ]);
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    /**
     * Display a listing of the resource.
     * @permission GroupProduitController::destroy
     * @permission_desc Supprimer un gorupe produits
     */
    public function destroy(string $id)
    {
        $group_product = GroupProduct::findOrFail($id);

        // Vérifie s'il est utilisé dans un produit
        $isUsed = Product::where('group_products_id', $id)->exists();

        if ($isUsed) {
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de supprimer : cette groupe de produit est utilisée par au moins un produit.'
            ], 400);
        }

        $group_product->is_deleted = true;
        $group_product->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Suppression éffectué avec succès'
        ]);
    }
}
