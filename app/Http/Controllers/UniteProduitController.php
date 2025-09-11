<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\UniteProduit;
use Illuminate\Http\Request;

/**
 * @permission_category Gestion des unités de produits
 */

class UniteProduitController extends Controller
{

    public function listIdName()
    {
        $unityProducts = UniteProduit::select('id', 'name')
            ->where('is_deleted', false)
            ->get();

        return response()->json([
            'unity_products' => $unityProducts
        ]);
    }
    /**
     * Display a listing of the resource.
     * @permission UniteProduitController::index
     * @permission_desc Afficher la liste des unités de produits
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 10);  // Par défaut, 10 éléments par page
        $page = $request->input('page', 1);  // Page courante
        $search = $request->input('search');
        $query = UniteProduit::where('is_deleted', false);
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%$search%");
            });
        }
        // Récupérer les assureurs avec pagination
        $unite_produits = UniteProduit::where('is_deleted', false)
            ->paginate($perPage);


        return response()->json([
            'data' => $unite_produits->items(),
            'current_page' => $unite_produits->currentPage(),  // Page courante
            'last_page' => $unite_produits->lastPage(),  // Dernière page
            'total' => $unite_produits->total(),  // Nombre total d'éléments
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
     * @permission UniteProduitController::store
     * @permission_desc Enregistrer les unités de produits
     */
    public function store(Request $request)
    {
        $auth = auth()->user();
        $data = $request->validate([
            'name' => 'required|string|unique:unite_produits,name',
            'code'=>'required|string|unique:unite_produits,code',
        ]);
        $data['created_by'] = $auth->id;
        $unite_produits = UniteProduit::create($data);
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
     * @permission UniteProduitController::show
     * @permission_desc Afficher les détails des unités de produits
     */
    public function show(string $id)
    {
        $unite_produit = UniteProduit::where('id', $id)->where('is_deleted', false)->first();
        if(!$unite_produit){
            return response()->json(['message' => 'Unite produit n\'existe pas',404]);
        }else{
            return response()->json($unite_produit);
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
     * Display a listing of the resource.
     * @permission UniteProduitController::update
     * @permission_desc Modifier les unités de produits
     */
    public function update(Request $request, string $id)
    {
        $unite_produit = UniteProduit::where('id', $id)->where('is_deleted', false)->first();

        // Valider les données reçues
        $data = $request->validate([
            'name' => 'required|string|unique:unite_produits,name,' . $id,
            'code'=>'required'
        ]);

        // Mettre à jour les données
        $unite_produit->update($data);

        // Retourner la réponse avec les données mises à jour
        return response()->json([
            'data' => $unite_produit,
            'message' => 'Mise à jour effectuée avec succès'
        ]);
        //
    }

    /**
     * Display a listing of the resource.
     * @permission UniteProduitController::destroy
     * @permission_desc Supprimer des unités de produits
     */
    public function destroy(string $id)
    {
        $unite_product = UniteProduit::findOrFail($id);

        // Vérifie s'il est utilisé dans un produit
        $isUsed = Product::where('unite_produits_id', $id)->exists();

        if ($isUsed) {
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de supprimer : cette unité de produit est utilisée par au moins un produit.'
            ], 400);
        }

        $unite_product->is_deleted = true;
        $unite_product->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Suppression éffectué avec succès'
        ]);
    }
}
