<?php

namespace App\Http\Controllers;

use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseOrderType;
use App\Models\EmplacementsProduct;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;

class PurchaseOrderController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'purchase_order_number' => 'nullable|string|max:255|unique:purchase_orders,purchase_order_number',
            'purchase_order_type' => ['required', new Enum(PurchaseOrderType::class)],
            'order_date'            => 'nullable|date',
            'expected_delivery_date'=> 'nullable|date|after_or_equal:order_date',
            'fournisseur_id'        => 'nullable|exists:fournisseurs,id',
            'destination_location_id'=> 'nullable|exists:emplacements_products,id',
            'description'           => 'nullable|string',

            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|exists:products,id',
            'items.*.quantity'      => 'required|integer|min:1',
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

            if ($request->purchase_order_type === PurchaseOrderType::INTERNAL->value) {
                $currentYear = now()->format('Y');
                $countThisYear = PurchaseOrder::where('purchase_order_type', PurchaseOrderType::INTERNAL->value)
                    ->whereYear('created_at', $currentYear)
                    ->count();

                $nextSequence = $countThisYear + 1;

                $purchaseOrderNumber = 'BC-INT-' . $currentYear . '-' . str_pad($nextSequence, 5, '0', STR_PAD_LEFT);
            }

            if (
                $request->purchase_order_type === PurchaseOrderType::EXTERNAL->value &&
                empty($purchaseOrderNumber)
            ) {
                throw new \Exception("Le numéro de bon de commande est requis pour un bon EXTERNE.");
            }

            $primaryLocation = EmplacementsProduct::where('is_primary', true)->first();

            if (!$primaryLocation) {
                throw new \Exception("Aucun emplacement principal défini.");
            }

            $purchaseOrder = PurchaseOrder::create([
                'purchase_order_number'     => $purchaseOrderNumber,
                'purchase_order_type'       => $request->purchase_order_type,
                'order_date'                => now(),
                'expected_delivery_date'    => $request->expected_delivery_date,
                'fournisseur_id'           => $request->fournisseur_id,
                'destination_location_id'  => $request->destination_location_id,
                'destination_source_id' => $primaryLocation->id,
                'status'                   => 'pending',
                'description'              => $request->description,
                'created_by'               => auth()->id(),
                'updated_by'               => auth()->id(),
            ]);

            foreach ($request->items as $item) {

                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id'        => $item['product_id'],
                    'quantity'          => $item['quantity'],
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


    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'purchase_order_number' => 'nullable|string|max:255|unique:purchase_orders,purchase_order_number,' . $id,
            'purchase_order_type' => ['required', new Enum(PurchaseOrderType::class)],
            'order_date' => 'nullable|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'fournisseur_id' => 'nullable|exists:fournisseurs,id',
            'destination_location_id' => 'nullable|exists:emplacements_products,id',
            'description' => 'nullable|string',

            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Les données sont invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {

            $purchaseOrder = PurchaseOrder::findOrFail($id);

            $purchaseOrderNumber = $request->purchase_order_number ?? $purchaseOrder->purchase_order_number;
            if ($request->purchase_order_type === PurchaseOrderType::INTERNAL->value) {
                if (!$purchaseOrder->purchase_order_number || !str_starts_with($purchaseOrder->purchase_order_number, 'BC-INT-')) {

                    $currentYear = now()->format('Y');
                    $countThisYear = PurchaseOrder::whereYear('created_at', $currentYear)->count();
                    $nextSequence = $countThisYear + 1;

                    $purchaseOrderNumber = 'BC-INT-' . $currentYear . '-' . str_pad($nextSequence, 5, '0', STR_PAD_LEFT);
                }
            }

            if (
                $request->purchase_order_type === PurchaseOrderType::EXTERNAL->value &&
                empty($purchaseOrderNumber)
            ) {
                throw new \Exception("Le numéro de bon de commande est requis pour un bon EXTERNE.");
            }

            $primaryLocation = EmplacementsProduct::where('is_primary', true)->first();

            if (!$primaryLocation) {
                throw new \Exception("Aucun emplacement principal défini.");
            }

            $purchaseOrder->update([
                'purchase_order_number' => $purchaseOrderNumber,
                'purchase_order_type' => $request->purchase_order_type,
                'order_date' => $request->order_date ?? $purchaseOrder->order_date,
                'expected_delivery_date' => $request->expected_delivery_date,
                'fournisseur_id' => $request->fournisseur_id,
                'destination_location_id' => $request->destination_location_id,
                'destination_source_id' => $primaryLocation->id,

                'status' => PurchaseOrderStatus::PENDING->value,

                'description' => $request->description,
                'updated_by' => auth()->id(),
            ]);

            PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)->delete();

            foreach ($request->items as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'description' => $item['description'] ?? null,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bon de commande mis à jour avec succès.',
                'data' => $purchaseOrder->fresh([
                    'items.product',
                    'fournisseur',
                    'destinationLocation',
                    'destinationSource'
                ]),
            ], 200);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du bon de commande.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


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


    public function show(string $id)
    {
        try {
            $purchaseOrder = PurchaseOrder::with([
                'items.product',
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

    public function reject(Request $request, string $id)
    {
        try {

            $request->validate([
                'reason_of_rejection' => 'required|string|max:1000',
            ]);

            $purchaseOrder = PurchaseOrder::findOrFail($id);

            if ($purchaseOrder->status === PurchaseOrderStatus::REJECTED->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette commande est déjà rejetée.',
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
                    'message' => 'Impossible de rejeter une commande déjà reçue.',
                ], 400);
            }

            if ($purchaseOrder->status === PurchaseOrderStatus::CANCELLED->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de rejeter une commande annulée.',
                ], 400);
            }

            $purchaseOrder->update([
                'status' => PurchaseOrderStatus::REJECTED->value,
                'rejected_at' => now(),
                'reason_of_rejection' => $request->reason_of_rejection,
                'rejected_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Commande rejetée avec succès.',
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
                'message' => 'Erreur lors du rejet de la commande.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


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


}
