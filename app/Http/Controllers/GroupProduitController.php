<?php

namespace App\Http\Controllers;

use App\Models\GroupProduct;
use Illuminate\Http\Request;

class GroupProduitController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 10);  // Par défaut, 10 éléments par page
        $page = $request->input('page', 1);  // Page courante

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
     * Store a newly created resource in storage.
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
    public function destroy(string $id)
    {
        //
    }
}
