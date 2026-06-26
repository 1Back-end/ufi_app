<?php

namespace App\Http\Controllers;

use App\Exports\AssureurExport;
use App\Exports\ProductsExport;
use App\Exports\ProductsExportSearch;
use App\Imports\MaladieImport;
use App\Imports\ProductsImport;
use App\Imports\ProductsOtherImport;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * @permission_category Gestion des produits
 * @permission_module Gestion des stocks
 */

class ProduitController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission ProduitController::index
     * @permission_desc Afficher la liste des produits
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);
        $search = $request->input('search');

        $products = Product::with([
                'voieTransmission:id,name',
                'uniteProduit:id,name',
                'groupProduct:id,name',
                'categories:id,name',
                'fournisseurs:id,full_name',
                'creator',
                'updater',
                'lots',
                'emplacements.emplacement'
            ])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {

                    $q->where('ref', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('generic_name', 'like', "%{$search}%")
                        ->orWhere('manufacturer_reference', 'like', "%{$search}%")
                        ->orWhere('product_type', 'like', "%{$search}%")
                        ->orWhere('dosage', 'like', "%{$search}%")
                        ->orWhere('laboratory_family', 'like', "%{$search}%")
                        ->orWhere('storage_unit', 'like', "%{$search}%")
                        ->orWhere('consumption_unit', 'like', "%{$search}%")
                        ->orWhere('storage_temperature', 'like', "%{$search}%")
                        ->orWhere('price', 'like', "%{$search}%")
                        ->orWhere('purchase_price', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('facturable'), function ($query) use ($request) {
                $query->where('facturable', $request->facturable);
            })
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

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
     * @permission_desc Activer/Désactiver un produit
     */

    public function updateStatus(Request $request, string $id)
    {
        $auth = auth()->user();
        $request->validate([
            'is_active' => 'required|boolean',
        ],[
            'is_active.required' => 'Le statut est obligatoire.',
        ]);
        $type = Product::where('id', $id)->first();
        $type->is_active = $request->is_active;
        $type->updated_by = $auth->id;
        $type->save();
        return response()->json([
            'success' => true,
            "message" => "Statut modifié avec succès"
        ]);
    }


    /**
     * Display a listing of the resource.
     * @permission ProduitController::store
     * @permission_desc Créer un produit
     */
    public function store(Request $request)
    {
        $auth = auth()->user();

        try {

            $data = $request->validate([
                'name' => 'required|string|unique:products,name',
                'product_type' => 'required|string',
                'dosage' => 'required|string',

                'generic_name' => 'nullable|string',
                'manufacturer_reference' => 'nullable|string',
                'laboratory_family' => 'nullable|string',
                'storage_unit' => 'nullable|string',
                'consumption_unit' => 'nullable|string',
                'conversion_factor' => 'nullable|numeric|min:1',
                'alert_threshold' => 'nullable|numeric|min:0',
                'minimum_threshold' => 'nullable|numeric|min:0',
                'storage_temperature' => 'nullable|string',

                'purchase_price' => 'nullable|numeric|min:0',
                'price' => 'nullable|numeric|min:0',

                'facturable' => 'nullable|boolean',

                'fournisseurs_id' => 'nullable|array',
                'fournisseurs_id.*' => 'exists:fournisseurs,id',

                'emplacements_id' => 'nullable|array',
                'emplacements_id.*' => 'exists:emplacements_products,id',

                'numero_lot_fabricant' => 'nullable|string',
                'date_reception' => 'nullable|date',
                'date_peremption' => 'nullable|date',
                'quantite_actuelle' => 'nullable|numeric|min:0',

                'Dosage_defaut' => 'nullable|string',
                'schema_administration' => 'nullable|string',
            ]);

            $fournisseurs = $data['fournisseurs_id'] ?? [];
            $emplacements = $data['emplacements_id'] ?? [];

            unset($data['fournisseurs_id'], $data['emplacements_id']);

            $data['ref'] = 'PROD' . now()->format('ymdHis') . rand(100, 999);
            $data['name'] = strtoupper($data['name']);
            $data['created_by'] = $auth->id;
            $data['facturable'] = $data['facturable'] ?? false;
            $data['status'] = $data['status'] ?? 'ACTIVE';

            $product = Product::create($data);

            $product->fournisseurs()->sync($fournisseurs);

            if (!empty($emplacements)) {
                foreach ($emplacements as $emplacementId) {
                    \App\Models\EmplacementProduit::create([
                        'id_produit' => $product->id,
                        'id_emplacement' => $emplacementId,
                        'created_by' => $auth->id,
                    ]);
                }
            }

            if (!empty($data['numero_lot_fabricant']) || !empty($data['quantite_actuelle'])) {
                \App\Models\LotProduit::create([
                    'numero_lot_fabricant' => $data['numero_lot_fabricant'] ?? null,
                    'date_reception' => $data['date_reception'] ?? null,
                    'date_peremption' => $data['date_peremption'] ?? null,
                    'quantite_actuelle' => $data['quantite_actuelle'] ?? 0,
                    'id_produit' => $product->id,
                    'id_emplacement' => $emplacements[0] ?? null,
                    'created_by' => $auth->id,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Produit créé avec succès.',
                'data' => $product->load(['fournisseurs'])
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission ProduitController::show
     * @permission_desc Afficher les détails  d'un produits
     */
    public function show(string $id)
    {
        try {
            $products = Product::with([
                'voieTransmission:id,name',
                'uniteProduit:id,name',
                'groupProduct:id,name',
                'categories:id,name',
                'fournisseurs:id,full_name',
                'creator',
                'updater',
                'lots',
                'emplacements.emplacement'
            ])
                ->findOrFail($id);

            return response()->json([
                'data' => $products,
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
     * Display a listing of the resource.
     * @permission ProduitController::update
     * @permission_desc Modifier un produit
     */
    public function update(Request $request, $id)
    {
        $auth = auth()->user();

        try {

            $product = Product::findOrFail($id);

            $data = $request->validate([
                'name' => 'required|string|unique:products,name,' . $id,
                'product_type' => 'required|string',
                'dosage' => 'required|string',

                'generic_name' => 'nullable|string',
                'manufacturer_reference' => 'nullable|string',
                'laboratory_family' => 'nullable|string',
                'storage_unit' => 'nullable|string',
                'consumption_unit' => 'nullable|string',
                'conversion_factor' => 'nullable|numeric|min:1',
                'alert_threshold' => 'nullable|numeric|min:0',
                'minimum_threshold' => 'nullable|numeric|min:0',
                'storage_temperature' => 'nullable|string',

                'purchase_price' => 'nullable|numeric|min:0',
                'price' => 'nullable|numeric|min:0',

                'facturable' => 'nullable|boolean',

                'fournisseurs_id' => 'nullable|array',
                'fournisseurs_id.*' => 'exists:fournisseurs,id',

                'emplacements_id' => 'nullable|array',
                'emplacements_id.*' => 'exists:emplacements_products,id',

                'numero_lot_fabricant' => 'nullable|string',
                'date_reception' => 'nullable|date',
                'date_peremption' => 'nullable|date',
                'quantite_actuelle' => 'nullable|numeric|min:0',
            ]);

            $fournisseurs = $data['fournisseurs_id'] ?? [];
            $emplacements = $data['emplacements_id'] ?? [];

            unset($data['fournisseurs_id'], $data['emplacements_id']);


            $data['name'] = strtoupper($data['name']);
            $data['updated_by'] = $auth->id;
            $data['facturable'] = $data['facturable'] ?? false;

            $product->update($data);


            $product->fournisseurs()->sync($fournisseurs);


            \App\Models\EmplacementProduit::where('id_produit', $product->id)->delete();

            foreach ($emplacements as $emplacementId) {
                \App\Models\EmplacementProduit::create([
                    'id_produit' => $product->id,
                    'id_emplacement' => $emplacementId,
                    'created_by' => $auth->id,
                    'updated_by' => $auth->id,
                ]);
            }

            $lot = \App\Models\LotProduit::where('id_produit', $product->id)
                ->where('statut', 'Disponible')
                ->first();

            if ($lot) {
                $lot->update([
                    'numero_lot_fabricant' => $data['numero_lot_fabricant'] ?? $lot->numero_lot_fabricant,
                    'date_reception' => $data['date_reception'] ?? $lot->date_reception,
                    'date_peremption' => $data['date_peremption'] ?? $lot->date_peremption,
                    'quantite_actuelle' => $data['quantite_actuelle'] ?? $lot->quantite_actuelle,
                    'updated_by' => $auth->id,
                ]);
            } else {
                if (!empty($data['numero_lot_fabricant'])) {
                    \App\Models\LotProduit::create([
                        'numero_lot_fabricant' => $data['numero_lot_fabricant'],
                        'date_reception' => $data['date_reception'] ?? null,
                        'date_peremption' => $data['date_peremption'] ?? null,
                        'quantite_actuelle' => $data['quantite_actuelle'] ?? 0,
                        'id_produit' => $product->id,
                        'id_emplacement' => $emplacements[0] ?? null,
                        'created_by' => $auth->id,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Produit mis à jour avec succès.',
                'data' => $product->load(['fournisseurs'])
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function import(Request $request)
    {

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new ProductsImport(), $request->file('file'));
            return response()->json(['message' => 'Importation réussie.']);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }



    public function import_others_products(Request $request)
    {

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new ProductsOtherImport(), $request->file('file'));
            return response()->json(['message' => 'Importation réussie.']);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }

    public function  Tarification_Products()
    {

        $products = Product::with(["categories", "fournisseurs"])->where("is_deleted", false)->orderBy("name")->get();


        $data = [
            'products' => $products,
        ];

        $fileName   = 'tarifaire-products-' . now()->format('YmdHis') . '.pdf';
        $folderPath = "storage/tarifaire-products";
        $filePath   = $folderPath . '/' . $fileName;


        if (!file_exists($folderPath)) {
            mkdir($folderPath, 0755, true);
        }

        save_browser_shot_pdf(
            view: 'pdfs.tarifaire-products.tarifaire-products',
            data: $data,
            folderPath: $folderPath,
            path: $filePath,
            margins: [10, 10, 10, 10],
            format: 'A4'
        );

        if (!file_exists($filePath)) {
            DB::rollBack();
            return response()->json(['error' => 'Le fichier PDF n\'a pas été généré.'], 500);
        }

        DB::commit();

        $pdfContent = file_get_contents($filePath);
        $base64 = base64_encode($pdfContent);

        return response()->json([
            'base64'   => $base64,
            'url'      => $filePath,
            'filename' => $fileName,
        ], 200);


    }


}
