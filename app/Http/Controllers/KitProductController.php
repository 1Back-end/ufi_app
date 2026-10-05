<?php

namespace App\Http\Controllers;

use App\Models\KitProduct;
use App\Models\KitProductItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @permission_category Gestion des kits de produits
 * @permission_module Gestion des stocks
 */

class KitProductController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission KitProductController::index
     * @permission_desc Afficher la liste des kits de produits
     */
    public function index(Request $request): JsonResponse
    {
        $kits = KitProduct::with(['items.product', 'creator'])->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $kits
        ], 200);
    }

    /**
     * Display a listing of the resource.
     * @permission KitProductController::store
     * @permission_desc Créer un kit de produits
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'nullable|string|unique:kit_products,code|unique:products,ref',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:products,id',
        ]);

        try {
            $kit = DB::transaction(function () use ($validated) {
                $code = $validated['code'] ?? 'KIT-' . strtoupper(Str::random(8));

                $kit = KitProduct::create([
                    'code' => $code,
                    'name' => $validated['name'],
                    'price' => $validated['price'],
                    'is_active' => $validated['is_active'] ?? true,
                    'created_by' => auth()->id(),
                ]);

                Product::create([
                    'ref' => $code,
                    'name' => $validated['name'],
                    'price' => $validated['price'],
                    'is_kit' => true,
                    'facturable' => false,
                    'kit_id' => $kit->id,
                    'created_by' => auth()->id(),
                ]);

                foreach ($validated['products'] as $item) {
                    KitProductItem::create([
                        'kit_id' => $kit->id,
                        'product_id' => $item['id'],
                        'created_by' => auth()->id(),
                    ]);
                }

                return $kit;
            });

            $kit->load('items.product');

            return response()->json([
                'success' => true,
                'message' => 'Kit et produit associé créés avec succès.',
                'data' => $kit
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du kit.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Display a listing of the resource.
     * @permission KitProductController::update
     * @permission_desc Modifier un kit de produits
     */
    public function update(Request $request, KitProduct $kit): JsonResponse
    {
        $product = Product::where('kit_id', $kit->id)->first();
        $productId = $product ? $product->id : null;

        $validated = $request->validate([
            'code' => 'nullable|string|unique:kit_products,code,' . $kit->id . '|unique:products,ref,' . $productId,
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:products,id',
        ]);

        DB::beginTransaction();

        try {
            $code = $validated['code'] ?? $kit->code;

            $kit->update([
                'code' => $code,
                'name' => $validated['name'],
                'price' => $validated['price'],
                'is_active' => $validated['is_active'] ?? $kit->is_active,
                'updated_by' => auth()->id(),
            ]);

            Product::where('kit_id', $kit->id)->update([
                'ref' => $code,
                'name' => $validated['name'],
                'price' => $validated['price'],
                'updated_by' => auth()->id(),
            ]);

            $kit->items()->delete();

            foreach ($validated['products'] as $item) {
                KitProductItem::create([
                    'kit_id' => $kit->id,
                    'product_id' => $item['id'],
                    'created_by' => auth()->id(),
                ]);
            }

            DB::commit();

            $kit->load('items.product');

            return response()->json([
                'success' => true,
                'message' => 'Kit mis à jour avec succès.',
                'data' => $kit
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du kit.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Display a listing of the resource.
     * @permission KitProductController::updateStatus
     * @permission_desc Activer/Désactiver un kit de produits
     */
    public function updateStatus(Request $request, KitProduct $kit): JsonResponse
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        try {
            $kit->update([
                'is_active' => $validated['is_active'],
                'updated_by' => auth()->id(),
            ]);

            Product::where('kit_id', $kit->id)->update([
                'is_active' => $validated['is_active'],
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Statut du kit mis à jour avec succès.',
                'data' => [
                    'id' => $kit->id,
                    'code' => $kit->code,
                    'name' => $kit->name,
                    'is_active' => $kit->is_active,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du statut.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
