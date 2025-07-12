<?php

namespace App\Http\Controllers;

use App\Exports\AssureurExport;
use App\Exports\ProductsExport;
use App\Exports\ProductsExportSearch;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProduitController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission ProduitController::index
     * @permission_desc Afficher la liste des produits
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 10);   // Nombre d'éléments par page (par défaut 10)
        $page = $request->input('page', 1);        // Page courante
        $search = $request->input('search');       // Terme de recherche

        $products = Product::where('is_deleted', false)
            ->with([
                'voieTransmission:id,name',
                'uniteProduit:id,name',
                'groupProduct:id,name',
                'categories:id,name',
                'fournisseurs:id,nom'
            ])
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('dosage', 'like', '%' . $search . '%')
                        ->orWhere('price', 'like', '%' . $search . '%')
                        ->orWhere('unite_par_emballage', 'like', '%' . $search . '%')
                        ->orWhere('condition_par_unite_emballage', 'like', '%' . $search . '%');

                    // Ajoute d'autres champs ici si besoin
                });
            })
            ->when($request->input('facturable'), function ($query) {
                $query->where('facturable', request('facturable'));
            })
            ->latest()
            ->paginate(perPage: $perPage, page: $page);

        return response()->json([
            'data' => $products->items(),
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'per_page' => $products->perPage(),
            'total' => $products->total(),
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission ProduitController::updateStatus
     * @permission_desc Changer le statut des produits
     */

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
     * Display a listing of the resource.
     * @permission ProduitController::store
     * @permission_desc Créer  des produits
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
                'categories_id' => 'required|array',
                'categories_id.*' => 'exists:categories,id',
                'fournisseurs_id' => 'required|array',
                'fournisseurs_id.*' => 'exists:fournisseurs,id',
                'unite_par_emballage' => 'required|integer',
                'condition_par_unite_emballage' => 'required|string',
                'Dosage_defaut' => 'required|string',
                'schema_administration' => 'required|string',
                'facturable' => 'required|boolean', // <- ici
            ]);

            $categories = $data['categories_id'];
            $fournisseurs = $data['fournisseurs_id'];
            unset($data['categories_id'], $data['fournisseurs_id']);

            $data['ref'] = 'PROD' . now()->format('ymdHis') . mt_rand(10, 99);
            $data['created_by'] = $auth->id;

            $product = Product::create($data);

            $product->categories()->sync($categories);
            $product->fournisseurs()->sync($fournisseurs);

            return response()->json([
                'data' => $product->load(['categories', 'fournisseurs']),
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
     * Display a listing of the resource.
     * @permission ProduitController::search
     * @permission_desc Rechercher des produits
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'nullable|string|max:255',
        ]);

        $searchQuery = $request->input('query', '');

        $query = Product::where('is_deleted', false);

        if ($searchQuery) {
            $query->where(function($query) use ($searchQuery) {
                $query->where('name', 'like', '%' . $searchQuery . '%')
                    ->orWhere('dosage', 'like', '%' . $searchQuery . '%')
                    ->orWhere('price', 'like', '%' . $searchQuery . '%')
                    ->orWhere('unite_par_emballage', 'like', '%' . $searchQuery . '%')
                    ->orWhere('condition_par_unite_emballage', 'like', '%' . $searchQuery . '%');
            });
        }

        $products= $query
            ->with([
                'voieTransmission:id,name',
                'uniteProduit:id,name',
                'groupProduct:id,name',
                'categories:id,name',
                'fournisseurs:id,nom',
            ]) // chargement des relations
            ->get();

        return response()->json([
            'data' => $products,
        ]);
    }


    /**
     * Display a listing of the resource.
     * @permission ProduitController::show
     * @permission_desc Afficher les détails produits
     */
    public function show(string $id)
    {
        try {
            $product = Product::where('is_deleted', false)
                ->with([
                    'voieTransmission:id,name',
                    'uniteProduit:id,name',
                    'groupProduct:id,name',
                    'categories:id,name',
                    'fournisseurs:id,nom',
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
     * Display a listing of the resource.
     * @permission ProduitController::export
     * @permission_desc Exporter les produits
     */
    public function export()
    {
        $fileName = 'produits-' . Carbon::now()->format('Y-m-d_H-i-s') . '.xlsx';

        Excel::store(new ProductsExport(), $fileName, 'exportproducts');

        return response()->json([
            "message" => "Exportation des données effectuée avec succès",
            "filename" => $fileName,
            "url" => Storage::disk('exportproducts')->url($fileName)
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission ProduitController::update
     * @permission_desc Mettre à jour produits
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();

        try {
            $product = Product::findOrFail($id);

            $data = $request->validate([
                'name' => 'required|string|unique:products,name,' . $product->id,
                'dosage' => 'required|string',
                'voix_transmissions_id' => 'required|exists:voix_transmissions,id',
                'price' => 'required|numeric',
                'unite_produits_id' => 'required|exists:unite_produits,id',
                'group_products_id' => 'required|exists:group_products,id',
                'categories_id' => 'required|array',
                'categories_id.*' => 'exists:categories,id',
                'fournisseurs_id' => 'required|array',
                'fournisseurs_id.*' => 'exists:fournisseurs,id',
                'unite_par_emballage' => 'required|integer',
                'condition_par_unite_emballage' => 'required|string',
                'Dosage_defaut' => 'required|string',
                'schema_administration' => 'required|string',
                'facturable' => 'required|boolean', // <- ici
            ]);

            $categories = $data['categories_id'];
            $fournisseurs = $data['fournisseurs_id'];
            unset($data['categories_id'], $data['fournisseurs_id']);

            $data['updated_by'] = $auth->id;

            $product->update($data);

            $product->categories()->sync($categories);
            $product->fournisseurs()->sync($fournisseurs);

            return response()->json([
                'data' => $product->load(['categories', 'fournisseurs']),
                'message' => 'Produit mis à jour avec succès.'
            ]);

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
     * @permission ProduitController::searchAndExport
     * @permission_desc Rechercher et exporter les produits
     */
    public function searchAndExport(Request $request)
    {
        $request->validate([
            'query' => 'nullable|string|max:255',
        ]);

        $searchQuery = $request->input('query', '');

        $query = Product::where('is_deleted', false);

        if ($searchQuery) {
            $query->where(function($query) use ($searchQuery) {
                $query->where('name', 'like', '%' . $searchQuery . '%')
                    ->orWhere('dosage', 'like', '%' . $searchQuery . '%')
                    ->orWhere('price', 'like', '%' . $searchQuery . '%')
                    ->orWhere('unite_par_emballage', 'like', '%' . $searchQuery . '%')
                    ->orWhere('condition_par_unite_emballage', 'like', '%' . $searchQuery . '%');
            });
        }

        $products= $query
            ->with([
                'voieTransmission:id,name',
                'uniteProduit:id,name',
                'groupProduct:id,name',
                'categories:id,name',
                'fournisseurs:id,nom',
            ]) // chargement des relations
            ->get();

        if ($products->isEmpty()) {
            return response()->json([
                'message' => 'Aucune donnée trouvé pour cette recherche.',
                'data' => []
            ]);
        }
        $fileName = 'produits-recherches-' . Carbon::now()->format('Y-m-d H:i:s') . '.xlsx';

        Excel::store(new ProductsExportSearch($products), $fileName, 'exportproducts');

        return response()->json([
            "message" => "Exportation des données effectuée avec succès",
            "filename" => $fileName,
            "url" => Storage::disk('exportproducts')->url($fileName)
        ]);

    }


    /**
     * Display a listing of the resource.
     * @permission ProduitController::destroy
     * @permission_desc Supprimer les produits
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
