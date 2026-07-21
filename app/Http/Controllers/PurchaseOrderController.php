<?php

namespace App\Http\Controllers;

use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseOrderType;
use App\Models\EmplacementsProduct;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
/**
 * @permission_category Gestion des commandes
 * @permission_module Gestion des stocks
 */

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission PurchaseOrderController::store
     * @permission_desc Créer une commande
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'purchase_order_number' => 'nullable|string|max:255|unique:purchase_orders,purchase_order_number',
            'purchase_order_type'   => ['required', new Enum(PurchaseOrderType::class)],
            'order_date'            => 'nullable|date',
            'expected_delivery_date'=> 'nullable|date|after_or_equal:order_date',
            'fournisseur_id'        => 'nullable|exists:fournisseurs,id',
            'destination_location_id'=> 'nullable|exists:emplacements_products,id',
            'description'           => 'nullable|string',

            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|exists:products,id',
            'items.*.quantity'      => 'required|integer|min:1',
            'items.*.packaging_id'  => 'nullable|exists:packagings,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Les données sont invalides.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $purchaseOrderNumber = $request->purchase_order_number;
            $currentYear = now()->format('Y');

            if ($request->purchase_order_type === PurchaseOrderType::INTERNAL->value) {
                $countThisYear = PurchaseOrder::where('purchase_order_type', PurchaseOrderType::INTERNAL->value)
                    ->whereYear('created_at', $currentYear)
                    ->count();

                $nextSequence = $countThisYear + 1;
                $purchaseOrderNumber = 'BC-INT-' . $currentYear . '-' . str_pad($nextSequence, 5, '0', STR_PAD_LEFT);
            }

            if ($request->purchase_order_type === PurchaseOrderType::EXTERNAL->value) {
                if (empty($purchaseOrderNumber)) {
                    $countThisYear = PurchaseOrder::where('purchase_order_type', PurchaseOrderType::EXTERNAL->value)
                        ->whereYear('created_at', $currentYear)
                        ->count();

                    $nextSequence = $countThisYear + 1;
                    $purchaseOrderNumber = 'BC-EXT-' . $currentYear . '-' . str_pad($nextSequence, 5, '0', STR_PAD_LEFT);
                }
            }

            if (empty($purchaseOrderNumber)) {
                throw new \Exception("Le numéro de bon de commande n'a pas pu être déterminé.");
            }
            $primaryLocation = EmplacementsProduct::where('is_primary', true)->first();

            if (!$primaryLocation) {
                throw new \Exception("Aucun emplacement principal défini.");
            }

            $purchaseOrder = PurchaseOrder::create([
                'purchase_order_number'    => $purchaseOrderNumber,
                'purchase_order_type'      => $request->purchase_order_type,
                'order_date'               => now(),
                'expected_delivery_date'   => $request->expected_delivery_date,
                'fournisseur_id'          => $request->fournisseur_id,
                'destination_location_id' => $request->destination_location_id,
                'destination_source_id'   => $primaryLocation->id,
                'status'                  => 'pending',
                'description'             => $request->description,
                'created_by'              => auth()->id(),
                'updated_by'              => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id'        => $item['product_id'],
                    'quantity'          => $item['quantity'],
                    'packaging_id'      => $item['packaging_id'] ?? null,
                    'description'       => $item['description'] ?? null,
                    'created_by'        => auth()->id(),
                    'updated_by'        => auth()->id(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bon de commande créé avec succès.',
                'data'    => $purchaseOrder->load([
                    'items.product',
                    'items.packaging',
                    'fournisseur',
                    'destinationLocation'
                ]),
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du bon de commande.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Display a listing of the resource.
     * @permission PurchaseOrderController::update
     * @permission_desc Modifier une commande
     */
    public function update(Request $request, $id)
    {
        // 1. Recherche du Bon de Commande
        $purchaseOrder = PurchaseOrder::find($id);

        if (!$purchaseOrder) {
            return response()->json([
                'success' => false,
                'message' => 'Bon de commande introuvable.',
            ], 404);
        }

        // 2. Validation des données
        $validator = Validator::make($request->all(), [
            'purchase_order_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('purchase_orders', 'purchase_order_number')->ignore($purchaseOrder->id),
            ],
            'purchase_order_type'   => ['required', new Enum(PurchaseOrderType::class)],
            'order_date'            => 'nullable|date',
            'expected_delivery_date'=> 'nullable|date|after_or_equal:order_date',
            'fournisseur_id'        => 'nullable|exists:fournisseurs,id',
            'destination_location_id'=> 'nullable|exists:emplacements_products,id',
            'description'           => 'nullable|string',

            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|exists:products,id',
            'items.*.quantity'      => 'required|integer|min:1',
            'items.*.packaging_id'  => 'nullable|exists:packagings,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Les données sont invalides.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            // 3. Mise à jour de l'en-tête du Bon de Commande
            $purchaseOrder->update([
                'purchase_order_type'      => $request->purchase_order_type,
                'order_date'               => $request->order_date ?? $purchaseOrder->order_date,
                'expected_delivery_date'   => $request->expected_delivery_date,
                'fournisseur_id'          => $request->fournisseur_id,
                'destination_location_id' => $request->destination_location_id,
                'description'             => $request->description,
                'updated_by'              => auth()->id(),
            ]);

            if ($request->has('purchase_order_number') && !empty($request->purchase_order_number)) {
                $purchaseOrder->update([
                    'purchase_order_number' => $request->purchase_order_number
                ]);
            }

            $purchaseOrder->items()->delete();

            foreach ($request->items as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id'        => $item['product_id'],
                    'quantity'          => $item['quantity'],
                    'packaging_id'      => $item['packaging_id'] ?? null,
                    'description'       => $item['description'] ?? null,
                    'created_by'        => auth()->id(),
                    'updated_by'        => auth()->id(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bon de commande mis à jour avec succès.',
                'data'    => $purchaseOrder->load([
                    'items.product',
                    'items.packaging',
                    'fournisseur',
                    'destinationLocation'
                ]),
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du bon de commande.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Display a listing of the resource.
     * @permission PurchaseOrderController::index
     * @permission_desc Afficher la liste des commandes
     */
    public function index(Request $request)
    {

        $auth = auth()->user();
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $start_date = Carbon::parse($request->input('start_date'))->startOfDay();
        $end_date = Carbon::parse($request->input('end_date'))->endOfDay();

        $query = PurchaseOrder::with([
            'items.product',
            'creator',
            'updater',
            'destinationLocation',
            'fournisseur',
            'destinationSource'
        ]);

        if ($request->filled('purchase_order_type')) {
            $query->where('purchase_order_type', $request->purchase_order_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('destination_location_id')) {
            $query->where('destination_location_id', $request->destination_location_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$start_date, $end_date]);
        }


        if ($search = trim($request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('purchase_order_number', 'like', "%{$search}%")
                    ->orWhere('purchase_order_type', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('destination_location_id', 'like', "%{$search}%")
                    ->orWhere('fournisseur_id', 'like', "%{$search}%")

                    // 🔹 Entrepôts
                    ->orWhereHas('destinationSource', function ($qw) use ($search) {
                        $qw->where('id', 'like', "%{$search}%")
                            ->orWhere('equipement', 'like', "%{$search}%")
                            ->orWhere('zone_stockage', 'like', "%{$search}%")
                            ->orWhere('position_detaillee', 'like', "%{$search}%");
                    })
                    ->orWhereHas('destinationLocation', function ($qf) use ($search) {
                        $qf->where('id', 'like', "%{$search}%")
                            ->orWhere('zone_stockage', 'like', "%{$search}%")
                            ->orWhere('equipement', 'like', "%{$search}%")
                            ->orWhere('position_detaillee', 'like', "%{$search}%");
                    })

                    ->orWhereHas('items.product', function ($qp) use ($search) {
                        $qp->where('name', 'like', "%{$search}%")
                            ->orWhere('ref', 'like', "%{$search}%")
                            ->orWhere('generic_name', 'like', "%{$search}%");
                    });
            });
        }

        // 🔹 Pagination
        $data = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'         => $data->items(),
            'current_page' => $data->currentPage(),
            'last_page'    => $data->lastPage(),
            'total'        => $data->total(),
        ]);
    }


    /**
     * Display a listing of the resource.
     * @permission PurchaseOrderController::show
     * @permission_desc Afficher les détails d'une commande
     */
    public function show(string $id)
    {
        try {
            $purchaseOrder = PurchaseOrder::with([
                'items.product',
                'items.packaging',
                'creator',
                'updater',
                'destinationLocation',
                'destinationSource',
                'fournisseur',
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Détails de la commande récupérés avec succès.',
                'data' => $purchaseOrder,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Commande introuvable.',
            ], 404);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur lors de la récupération de la commande.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission PurchaseOrderController::cancel
     * @permission_desc Annuler une commande
     */
    public function cancel(string $id)
    {
        try {

            $purchaseOrder = PurchaseOrder::findOrFail($id);

            if ($purchaseOrder->status === PurchaseOrderStatus::CANCELLED->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette commande est déjà annulée.',
                ], 400);
            }

            if (
                in_array($purchaseOrder->status, [
                    PurchaseOrderStatus::RECEIVED->value,
                    PurchaseOrderStatus::PARTIALLY_RECEIVED->value
                ])
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible d’annuler une commande déjà reçue ou partiellement reçue.',
                ], 400);
            }

            $purchaseOrder->update([
                'status' => PurchaseOrderStatus::CANCELLED->value,
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Commande annulée avec succès.',
                'data' => $purchaseOrder->fresh([
                    'items.product',
                    'creator',
                    'destinationLocation',
                    'destinationSource',
                    'fournisseur',
                ]),
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l’annulation de la commande.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Display a listing of the resource.
     * @permission PurchaseOrderController::validate
     * @permission_desc Valider une commande
     */
    public function validate(string $id)
    {
        try {

            $purchaseOrder = PurchaseOrder::findOrFail($id);

            if ($purchaseOrder->status === PurchaseOrderStatus::IN_PROGRESS->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette commande est déjà en cours de traitement.',
                ], 400);
            }

            if (
                in_array($purchaseOrder->status, [
                    PurchaseOrderStatus::CANCELLED->value,
                    PurchaseOrderStatus::RECEIVED->value,
                    PurchaseOrderStatus::PARTIALLY_RECEIVED->value,
                ])
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de valider cette commande.',
                ], 400);
            }

            // ✅ validation
            $purchaseOrder->update([
                'status' => PurchaseOrderStatus::IN_PROGRESS->value,
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Commande validée avec succès.',
                'data' => $purchaseOrder->fresh([
                    'items.product',
                    'creator',
                    'destinationLocation',
                    'destinationSource',
                    'fournisseur',
                ]),
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation de la commande.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Display a listing of the resource.
     * @permission PurchaseOrderController::receive
     * @permission_desc Receptionner une commande
     */
    public function receive(Request $request, $id)
    {
        $purchaseOrder = PurchaseOrder::with('items')->findOrFail($id);

        // Validation des données entrantes (sans batch_number)
        $validator = Validator::make($request->all(), [
            'order_number' => $purchaseOrder->purchase_order_type === 'external' ? 'required|string|max:255' : 'nullable|string|max:255',
            'order_date'   => $purchaseOrder->purchase_order_type === 'external' ? 'required|date' : 'nullable|date',
            'received_date'=> 'required|date',

            'id_emplacement' => $purchaseOrder->purchase_order_type !== 'external' ? 'required|exists:emplacements_products,id' : 'nullable',

            'products_quantities' => 'required|array|min:1',
            'products_quantities.*.product_id' => 'required|exists:products,id',
            'products_quantities.*.received_quantity' => 'required|integer|min:0',
            'products_quantities.*.expiration_date' => $purchaseOrder->purchase_order_type === 'external' ? 'required|date' : 'nullable|date',
            'products_quantities.*.id_emplacement' => $purchaseOrder->purchase_order_type === 'external' ? 'required|exists:emplacements_products,id' : 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Les données de réception sont invalides.',
                'errors'  => $validator->errors(),
            ], 422);
        }


        if ($purchaseOrder->purchase_order_type === 'external' && $request->filled('order_number')) {
            $requestedOrderNumber = trim($request->order_number);

            $existsOnAnotherOrder = DB::table('approvisionnements')
                ->where('purchase_order_id', '!=', $purchaseOrder->id)
                ->where('order_number', $requestedOrderNumber)
                ->exists();

            if ($existsOnAnotherOrder) {
                return response()->json([
                    'success' => false,
                    'message' => "Ce numéro de bon de livraison ('{$request->order_number}') a déjà été utilisé pour un autre bon de commande.",
                ], 422);
            }
        }

        DB::beginTransaction();

        try {
            foreach ($request->products_quantities as $incomingItem) {
                $orderItem = PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)
                    ->where('product_id', $incomingItem['product_id'])
                    ->first();

                if (!$orderItem) {
                    continue;
                }

                $receivedQty = (int)$incomingItem['received_quantity'];

                if ($receivedQty > 0) {
                    $maxAllowed = $orderItem->quantity - $orderItem->already_received_quantity;
                    if ($receivedQty > $maxAllowed) {
                        return response()->json([
                            'success' => false,
                            'message' => "La quantité reçue pour l'article ID {$incomingItem['product_id']} dépasse la quantité restante à recevoir ({$maxAllowed})."
                        ], 422);
                    }

                    $emplacementId = $purchaseOrder->purchase_order_type === 'external'
                        ? $incomingItem['id_emplacement']
                        : ($request->id_emplacement ?: $purchaseOrder->destination_source_id);

                    $expirationDate = $incomingItem['expiration_date'] ?? null;

                    $newAlreadyReceived = $orderItem->already_received_quantity + $receivedQty;
                    $remainingQty = max($orderItem->quantity - $newAlreadyReceived, 0);

                    $orderItem->update([
                        'already_received_quantity' => $newAlreadyReceived,
                        'remaining_quantity'        => $remainingQty,
                        'updated_by'                => auth()->id(),
                    ]);

                    $lotQuery = DB::table('lot_produits')
                        ->where('id_produit', $incomingItem['product_id'])
                        ->where('id_emplacement', $emplacementId);

                    if ($expirationDate) {
                        $lotQuery->where('date_peremption', $expirationDate);
                    } else {
                        $lotQuery->whereNull('date_peremption');
                    }

                    $lot = $lotQuery->first();

                    if ($lot) {
                        DB::table('lot_produits')
                            ->where('id', $lot->id)
                            ->update([
                                'quantite_actuelle' => $lot->quantite_actuelle + $receivedQty,
                                'date_reception'    => $request->received_date,
                                'updated_by'        => auth()->id(),
                                'updated_at'        => now(),
                            ]);
                        $lotId = $lot->id;
                        $batchNumber = $lot->numero_lot_fabricant;
                    } else {
                        $lastId = DB::table('lot_produits')->max('id') ?? 0;
                        $nextNumber = str_pad($lastId + 1, 5, '0', STR_PAD_LEFT);

                        $expFormatted = $expirationDate
                            ? \Carbon\Carbon::parse($expirationDate)->format('Ymd')
                            : 'NOEXP';

                        $batchNumber = 'LOT-' . $nextNumber . '-' . $expFormatted;

                        $lotId = DB::table('lot_produits')->insertGetId([
                            'numero_lot_fabricant' => $batchNumber,
                            'date_peremption'      => $expirationDate,
                            'date_reception'       => $request->received_date,
                            'quantite_actuelle'    => $receivedQty,
                            'statut'               => 'Disponible',
                            'id_produit'           => $incomingItem['product_id'],
                            'id_emplacement'       => $emplacementId,
                            'fournisseur_id'       => $purchaseOrder->fournisseur_id,
                            'created_by'           => auth()->id(),
                            'updated_by'           => auth()->id(),
                            'created_at'           => now(),
                            'updated_at'           => now(),
                        ]);
                    }

                    // Enregistrement du mouvement de stock
                    DB::table('mouvement_stock')->insert([
                        'created_by'           => auth()->id(),
                        'updated_by'           => auth()->id(),
                        'lot_id'               => $lotId,
                        'type_mouvement'       => 'Entrée en stock',
                        'quantite_mutee'       => $receivedQty,
                        'description'          => "Réception via BC N° " . $purchaseOrder->purchase_order_number . " (Lot: " . $batchNumber . ")",
                        'date_heure_mouvement' => $request->received_date . ' ' . now()->format('H:i:s'),
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ]);

                    // Enregistrement dans approvisionnements
                    DB::table('approvisionnements')->insert([
                        'purchase_order_id' => $purchaseOrder->id,
                        'product_id'        => $incomingItem['product_id'],
                        'emplacement_id'    => $emplacementId,
                        'quantite_recue'    => $receivedQty,
                        'batch_number'      => $batchNumber,
                        'expiration_date'   => $expirationDate,
                        'order_number'      => $request->order_number ?? null,
                        'received_date'     => $request->received_date,
                        'created_by'        => auth()->id(),
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);
                }
            }

            // Mise à jour du statut global du bon de commande
            $allOrderItems = PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)->get();
            $totalItemsCount = $allOrderItems->count();

            $fullyReceivedItemsCount = $allOrderItems->filter(function($item) {
                return $item->already_received_quantity >= $item->quantity;
            })->count();

            $anyReceivedItemsCount = $allOrderItems->filter(function($item) {
                return $item->already_received_quantity > 0;
            })->count();

            if ($fullyReceivedItemsCount === $totalItemsCount) {
                $purchaseOrder->status = PurchaseOrderStatus::RECEIVED->value;
            } elseif ($anyReceivedItemsCount > 0) {
                $purchaseOrder->status = PurchaseOrderStatus::PARTIALLY_RECEIVED->value;
            } else {
                $purchaseOrder->status = PurchaseOrderStatus::IN_PROGRESS->value;
            }

            $purchaseOrder->updated_by = auth()->id();
            $purchaseOrder->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bon de commande réceptionné, lots mis à jour et approvisionnement enregistré avec succès.',
                'status'  => $purchaseOrder->status
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réception du bon de commande.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission PurchaseOrderController::toggleConfirmation
     * @permission_desc Confirmer ou mettre à jour la réception d'un bon de commande.
     */
    public function toggleConfirmation(Request $request, int $id): JsonResponse
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);
        $isConfirmed = $request->has('is_confirmed')
            ? $request->boolean('is_confirmed')
            : true;

        $purchaseOrder->update([
            'is_confirmed' => $isConfirmed,
        ]);

        return response()->json([
            'message' => 'Statut de confirmation mis à jour avec succès.',
            'data' => $purchaseOrder
        ], 200);
    }


}
