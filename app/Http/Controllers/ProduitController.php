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
 * @permission_module Gestion des prestations
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
                'fournisseurs:id,full_name',
                'creator',
                'updater',
                'lots',
                'productType',
                'packagings'
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
     * @permission ProduitController::updateSuspendedStatus
     * @permission_descModifier le statut de suspension du produit
     */
    public function updateSuspendedStatus(Request $request, string $id)
    {
        $auth = auth()->user();

        $request->validate([
            'is_suspended' => 'required|boolean',
        ], [
            'is_suspended.required' => 'Le statut de suspension est obligatoire.',
            'is_suspended.boolean'  => 'Le statut de suspension doit être un booléen.',
        ]);

        $product = Product::findOrFail($id);
        $product->is_suspended = $request->is_suspended;
        $product->updated_by   = $auth->id;
        $product->save();

        $statusMessage = $product->is_suspended ? 'Produit suspendu avec succès.' : 'Suspension du produit levée.';

        return response()->json([
            'success' => true,
            'message' => $statusMessage,
            'data'    => $product
        ]);
    }

    /**
     * Display a listing of the resource.
     * @permission ProduitController::updateOutOfStockStatus
     * @permission_desc Modifier le statut d'épuisement / rupture de stock
     */
    public function updateOutOfStockStatus(Request $request, string $id)
    {
        $auth = auth()->user();

        $request->validate([
            'is_out_of_stock' => 'required|boolean',
        ], [
            'is_out_of_stock.required' => 'Le statut de stock est obligatoire.',
            'is_out_of_stock.boolean'  => 'Le statut de stock doit être un booléen.',
        ]);

        $product = Product::findOrFail($id);
        $product->is_out_of_stock = $request->is_out_of_stock;
        $product->updated_by     = $auth->id;
        $product->save();

        $statusMessage = $product->is_out_of_stock ? 'Produit marqué comme épuisé.' : 'Produit marqué comme disponible.';

        return response()->json([
            'success' => true,
            'message' => $statusMessage,
            'data'    => $product
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
            $validatedData = $request->validate([
                'name'                   => 'required|string|unique:products,name',
                'product_type_id'        => 'required|exists:product_types,id',
                'barcode'                => 'nullable|string|unique:products,barcode',

                'manufacturer_reference' => 'nullable|string',
                'laboratory_family'      => 'nullable|string',
                'storage_unit'           => 'nullable|string',
                'consumption_unit'       => 'nullable|string',
                'conversion_factor'      => 'nullable|numeric|min:1',
                'alert_threshold'        => 'nullable|numeric|min:0',
                'minimum_threshold'      => 'nullable|numeric|min:0',
                'storage_temperature'    => 'nullable|string',

                'purchase_price'         => 'nullable|numeric|min:0',
                'price'                  => 'nullable|numeric|min:0',
                'pharmacy_price'         => 'nullable|numeric|min:0',

                'facturable'             => 'nullable|boolean',
                'allow_negative_stock'   => 'nullable|boolean',
                'has_expiration_date'    => 'nullable|boolean',
                'has_moratorium'         => 'nullable|boolean',
                'moratorium_months'      => 'nullable|required_if:has_moratorium,true|integer|min:1',

                'fournisseurs_id'        => 'nullable|array',
                'fournisseurs_id.*'      => 'exists:fournisseurs,id',


                'packagings_id'          => 'nullable|array',
                'packagings_id.*'        => 'exists:packagings,id',
                'packagings'             => 'nullable|array',

                'Dosage_defaut'          => 'nullable|string',
                'schema_administration'  => 'nullable|string',

                'dosage'                 => 'nullable|string',
                'generic_name'           => 'nullable|string',
            ]);

            $productType = \App\Models\ProductType::findOrFail($request->product_type_id);
            $fournisseursIds = $request->input('fournisseurs_id', []);
            $packagingsIds   = $request->input('packagings_id', $request->input('packagings', []));

            if ($productType->accepts_galenic_form && empty($request->dosage)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors'  => ['dosage' => ['Le dosage (forme galénique) est obligatoire pour ce type de produit.']]
                ], 422);
            }

            if ($productType->accepts_generic_form && empty($request->generic_name)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors'  => ['generic_name' => ['Le principe actif (DCI / Nom générique) est obligatoire pour ce type de produit.']]
                ], 422);
            }

            if ($productType->accepts_packaging && (empty($request->packagings) || count($request->packagings) === 0)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors'  => ['packagings' => ['Au moins un conditionnement est obligatoire pour ce type de produit.']]
                ], 422);
            }

            $data = $validatedData;
            unset($data['fournisseurs_id'], $data['packagings'], $data['packagings_id']);

            if (empty($data['generic_name'])) {
                $data['generic_name'] = \Illuminate\Support\Str::slug($data['name']);
            }

            $data['purchase_price'] = $data['purchase_price'] ?? 0;
            $data['pharmacy_price'] = $data['pharmacy_price'] ?? 0;
            $data['price']          = $data['price'] ?? 0;

            do {
                $ref = 'PROD' . now()->format('ymdHis') . rand(1000, 9999);
            } while (Product::where('ref', $ref)->exists());

            $data['ref']            = $ref;
            $data['name']       = strtoupper($data['name']);
            $data['created_by'] = $auth->id;
            $data['facturable'] = $data['facturable'] ?? false;
            $data['status']     = $data['status'] ?? 'ACTIVE';

            $product = Product::create($data);


            if (!empty($fournisseursIds)) {
                $product->fournisseurs()->sync($fournisseursIds);
            }

            if (!empty($packagingsIds)) {
                $syncData = [];
                foreach ($packagingsIds as $packagingId) {
                    $syncData[$packagingId] = [
                        'created_by' => $auth->id,
                    ];
                }
                $product->packagings()->sync($syncData);
            }

            return response()->json([
                'success' => true,
                'message' => 'Produit créé avec succès.',
                'data'    => $product->load(['fournisseurs', 'productType', 'packagings'])
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue',
                'error'   => $e->getMessage()
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
                'fournisseurs:id,full_name',
                'creator',
                'updater',
                'lots',
                'emplacements.emplacement',
                'productType',
                'packagings'
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

            $validatedData = $request->validate([
                'name'                   => 'required|string|unique:products,name,' . $id,
                'product_type_id'        => 'required|exists:product_types,id',
                'barcode'                => 'nullable|string|unique:products,barcode,' . $id,

                'manufacturer_reference' => 'nullable|string',
                'laboratory_family'      => 'nullable|string',
                'storage_unit'           => 'nullable|string',
                'consumption_unit'       => 'nullable|string',
                'conversion_factor'      => 'nullable|numeric|min:1',
                'alert_threshold'        => 'nullable|numeric|min:0',
                'minimum_threshold'      => 'nullable|numeric|min:0',
                'storage_temperature'    => 'nullable|string',

                'purchase_price'         => 'nullable|numeric|min:0',
                'price'                  => 'nullable|numeric|min:0',
                'pharmacy_price'         => 'nullable|numeric|min:0',

                'facturable'             => 'nullable|boolean',
                'allow_negative_stock'   => 'nullable|boolean',
                'has_expiration_date'    => 'nullable|boolean',
                'has_moratorium'         => 'nullable|boolean',
                'moratorium_months'      => 'nullable|required_if:has_moratorium,true|integer|min:1',

                'fournisseurs_id'        => 'nullable|array',
                'fournisseurs_id.*'      => 'exists:fournisseurs,id',

                // Gestion flexible : accepte soit packagings_id [1, 2], soit packagings [{id: 1, is_default: true}]
                'packagings_id'          => 'nullable|array',
                'packagings_id.*'        => 'exists:packagings,id',
                'packagings'             => 'nullable|array',

                'Dosage_defaut'          => 'nullable|string',
                'schema_administration'  => 'nullable|string',

                'dosage'                 => 'nullable|string',
                'generic_name'           => 'nullable|string',
                'status'                 => 'nullable|string',
                'is_active'              => 'nullable|boolean',
            ]);

            $productType = \App\Models\ProductType::findOrFail($request->product_type_id);

            // Extraction souple des conditionnements (IDs simples ou tableau d'objets)
            $rawPackagings = $request->input('packagings', []);
            $rawPackagingsIds = $request->input('packagings_id', []);

            // Validation dynamique selon le type de produit
            if ($productType->accepts_galenic_form && empty($request->dosage)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors'  => ['dosage' => ['Le dosage (forme galénique) est obligatoire pour ce type de produit.']]
                ], 422);
            }

            if ($productType->accepts_generic_form && empty($request->generic_name)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors'  => ['generic_name' => ['Le principe actif (DCI / Nom générique) est obligatoire pour ce type de produit.']]
                ], 422);
            }

            if ($productType->accepts_packaging && empty($rawPackagings) && empty($rawPackagingsIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors'  => ['packagings' => ['Au moins un conditionnement est obligatoire pour ce type de produit.']]
                ], 422);
            }

            // Nettoyage de $data pour l'update du modèle
            $data = $validatedData;
            $fournisseurs = $data['fournisseurs_id'] ?? [];

            unset($data['fournisseurs_id'], $data['packagings'], $data['packagings_id']);

            if (empty($data['generic_name'])) {
                $data['generic_name'] = \Illuminate\Support\Str::slug($data['name']);
            }

            $data['purchase_price'] = $data['purchase_price'] ?? 0;
            $data['pharmacy_price'] = $data['pharmacy_price'] ?? 0;
            $data['price']          = $data['price'] ?? 0;

            $data['name']       = strtoupper($data['name']);
            $data['updated_by'] = $auth->id;

            if (isset($data['has_moratorium']) && !$data['has_moratorium']) {
                $data['moratorium_months'] = null;
            }

            // 1. Mise à jour des informations de base du produit
            $product->update($data);

            // 2. Synchronisation des fournisseurs
            $product->fournisseurs()->sync($fournisseurs);

            $syncData = [];

            // Cas A : format objets [{id: 1, is_default: true}]
            if (!empty($rawPackagings)) {
                foreach ($rawPackagings as $pkg) {
                    $pkgId = is_array($pkg) ? ($pkg['id'] ?? null) : $pkg;
                    if ($pkgId) {
                        $syncData[$pkgId] = [
                            'is_default' => is_array($pkg) ? ($pkg['is_default'] ?? false) : false,
                            'created_by' => $auth->id,
                            'updated_by' => $auth->id,
                        ];
                    }
                }
            }
            // Cas B : format IDs simples [1, 2]
            elseif (!empty($rawPackagingsIds)) {
                foreach ($rawPackagingsIds as $index => $pkgId) {
                    $syncData[$pkgId] = [
                        'is_default' => ($index === 0),
                        'created_by' => $auth->id,
                        'updated_by' => $auth->id,
                    ];
                }
            }

            // Synchronisation effective avec la table pivot product_packaging
            $product->packagings()->sync($syncData);

            return response()->json([
                'success' => true,
                'message' => 'Produit mis à jour avec succès.',
                'data'    => $product->load(['fournisseurs', 'productType', 'packagings'])
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue',
                'error'   => $e->getMessage()
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
