<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
class ProduitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 10); // Nombre d'éléments par page

        $products = Product::where('is_deleted', false)
            ->with([
                'voieTransmission:id,name',
                'uniteProduit:id,name',
                'groupProduct:id,name',
                'categorie:id,name',
                'fournisseur:id,nom',
            ])
            ->paginate($perPage);

        return response()->json([
            'data' => $products->items(),
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'per_page' => $products->perPage(),
            'total' => $products->total(),
        ]);
    }

    public function updateStatus(Request $request, $id, $status)
    {
        // Find the assureur by ID
        $products = Product::find($id);
        if (!$products) {
            return response()->json(['message' => 'Produit non trouvé'], 404);
        }

        // Check if the assureur is deleted
        if ($products->is_deleted) {
            return response()->json(['message' => 'Impossible de mettre à jour un produit supprimé'], 400);
        }

        // Validate the status
        if (!in_array($status, ['Actif', 'Inactif'])) {
            return response()->json(['message' => 'Statut invalide'], 400);
        }

        // Update the status
        $products->status = $status;  // Ensure the correct field name
        $products->save();

        // Return the updated assureur
        return response()->json([
            'message' => 'Statut mis à jour avec succès',
            'products' => $products  // Corrected to $assureur
        ], 200);
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
            $data = $request->validate([
                'name' => 'required|string|unique:products,name',
                'dosage' => 'required|string',
                'voix_transmissions_id' => 'required|exists:voix_transmissions,id',
                'price' => 'required|numeric',
                'unite_produits_id' => 'required|exists:unite_produits,id',
                'group_products_id' => 'required|exists:group_products,id',
                'categories_id' => 'required|exists:categories,id',
                'unite_par_emballage' => 'required|integer',
                'condition_par_unite_emballage' => 'required|integer',
                'fournisseurs_id' => 'required|exists:fournisseurs,id',
                'Dosage_defaut' => 'required|string',
                'schema_administration' => 'required|string',
            ]);

            $data['ref'] = 'PROD' . now()->format('ymdHis') . mt_rand(10, 99);
            $data['created_by'] = $auth->id;

            $product = Product::create($data);

            return response()->json([
                'data' => $product,
                'message' => 'Produit créé avec succès.'
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
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $product = Product::where('is_deleted', false)
                ->with([
                    'voieTransmission:id,name',
                    'uniteProduit:id,name',
                    'groupProduct:id,name',
                    'categorie:id,name',
                    'fournisseur:id,nom',
                ])
                ->findOrFail($id);

            return response()->json([
                'data' => $product,
                'message' => 'Détails du produit récupérés avec succès.'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Produit non trouvé.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur est survenue.',
                'message' => $e->getMessage()
            ], 500);
        }
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
            $product = Product::where('is_deleted', false)->findOrFail($id);

            $data = $request->validate([
                'name' => 'required|string|unique:products,name,' . $id,
                'dosage' => 'required|string',
                'voix_transmissions_id' => 'required|exists:voix_transmissions,id',
                'price' => 'required|numeric',
                'unite_produits_id' => 'required|exists:unite_produits,id',
                'group_products_id' => 'required|exists:group_products,id',
                'categories_id' => 'required|exists:categories,id',
                'unite_par_emballage' => 'required|integer',
                'condition_par_unite_emballage' => 'required|integer',
                'fournisseurs_id' => 'required|exists:fournisseurs,id',
                'Dosage_defaut' => 'required|string',
                'schema_administration' => 'required|string',
            ]);

            $data['updated_by'] = $auth->id;

            $product->update($data);

            return response()->json([
                'data' => $product,
                'message' => 'Produit mis à jour avec succès.'
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
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $product = Product::where('is_deleted', false)->findOrFail($id);

            $product->update([
                'is_deleted' => true
            ]);

            return response()->json([
                'message' => 'Produit supprimé avec succès.'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Produit non trouvé ou déjà supprimé.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Une erreur est survenue.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
