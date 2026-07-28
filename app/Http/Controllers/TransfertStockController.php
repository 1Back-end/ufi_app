<?php

namespace App\Http\Controllers;

use App\Models\Assureur;
use App\Models\LotProduit;
use App\Models\PurchaseOrder;
use App\Models\TransfertStock;
use App\Models\TransfertStockItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Quotation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @permission_category Gestion des transferts de stocks
 * @permission_module Gestion des stocks
 */

class TransfertStockController extends Controller
{
    /**
     * Display a listing of the resource.
     * @permission TransfertStockController::index
     * @permission_desc Afficher la liste des transferts de stocks
     */
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page    = $request->input('page', 1);

        $query = TransfertStock::with([
            'emplacementSource',
            'emplacementDestination',
            'items.product',
            'items.lot',
            'creator',
            'updater',
            'validator'
        ]);

        if ($request->filled('emplacement_source_id')) {
            $query->where('emplacement_source_id', $request->emplacement_source_id);
        }

        if ($request->filled('emplacement_destination_id')) {
            $query->where('emplacement_destination_id', $request->emplacement_destination_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
            $endDate   = Carbon::parse($request->input('end_date'))->endOfDay();

            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        if ($search = trim($request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('staff_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")

                    ->orWhereHas('emplacementSource', function ($qs) use ($search) {
                        $qs->where('zone_stockage', 'like', "%{$search}%")
                            ->orWhere('equipement', 'like', "%{$search}%")
                            ->orWhere('position_detaillee', 'like', "%{$search}%");
                    })

                    ->orWhereHas('emplacementDestination', function ($qd) use ($search) {
                        $qd->where('zone_stockage', 'like', "%{$search}%")
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
     * @permission TransfertStockController::store
     * @permission_desc Créer un transfert de stocks
     */
    public function store(Request $request)
    {
        $request->validate([
            'emplacement_source_id'      => 'required|array|min:1',
            'emplacement_source_id.*.id'  => 'required|integer|exists:emplacements_products,id',
            'emplacement_destination_id' => 'required|array|min:1',
            'emplacement_destination_id.*.id' => 'required|integer|exists:emplacements_products,id',
            'staff_name'                 => 'required|string|max:255',
            'description'                => 'nullable|string',
            'products_quantities'        => 'required|array|min:1',
            'products_quantities.*.id'   => 'required|integer|exists:products,id',
            'products_quantities.*.batch_id' => 'required|integer|exists:lot_produits,id',
            'products_quantities.*.quantity' => 'required|integer|min:1',
        ]);

        $sourceId = $request->emplacement_source_id[0]['id'];
        $destinationId = $request->emplacement_destination_id[0]['id'];

        if ($sourceId === $destinationId) {
            return response()->json([
                'success' => false,
                'message' => 'L\'emplacement source et la destination doivent être différents.'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $now = Carbon::now();
            $year = $now->format('Y');
            $month = $now->format('m');

            $countThisYear = TransfertStock::whereYear('created_at', $year)->count();
            $sequence = $countThisYear + 1;
            $paddedSequence = str_pad($sequence, 3, '0', STR_PAD_LEFT);
            $reference = "TR{$paddedSequence}{$month}-{$year}";

            $transfert = TransfertStock::create([
                'reference'                  => $reference,
                'emplacement_source_id'      => $sourceId,
                'emplacement_destination_id' => $destinationId,
                'staff_name'                 => $request->staff_name,
                'description'                => $request->description,
                'created_by'                 => auth()->id(),
                'updated_by'                 => auth()->id(),
            ]);

            foreach ($request->products_quantities as $item) {
                $lot = LotProduit::where('id', $item['batch_id'])->first();

                if (!$lot || $lot->quantite_actuelle < $item['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuffisant pour le lot " . ($lot->batch_number ?? $lot->numero_lot ?? 'sélectionné') . "."
                    ], 422);
                }

                TransfertStockItem::create([
                    'transfert_stock_id'   => $transfert->id,
                    'product_id'     => $item['id'],
                    'lot_produit_id' => $item['batch_id'],
                    'quantite'       => $item['quantity'],
                    'created_by'     => auth()->id(),
                    'updated_by'     => auth()->id(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transfert de stock effectué avec succès.',
                'data'    => $transfert->load('items.product', 'items.lot')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'enregistrement : ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Display a listing of the resource.
     * @permission TransfertStockController::cancel
     * @permission_desc Annuler un transfert de stocks
     */
    public function cancel($id)
    {
        $transfert = TransfertStock::find($id);

        if (!$transfert) {
            return response()->json([
                'success' => false,
                'message' => 'Transfert de stock introuvable.'
            ], 404);
        }

        if ($transfert->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Ce transfert est déjà annulé.'
            ], 422);
        }

        if ($transfert->status === 'validated') {
            return response()->json([
                'success' => false,
                'message' => 'Impossible d\'annuler un transfert déjà validé.'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $transfert->update([
                'status'       => 'cancelled',
                'cancelled_by' => auth()->id(),
                'cancelled_at' => Carbon::now(),
                'updated_by'   => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transfert de stock annulé avec succès.',
                'data'    => $transfert->load('canceller')
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'annulation : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission TransfertStockController::reject
     * @permission_desc Rejetter un transfert de stocks
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ], [
            'reason.required' => 'Le motif du rejet est obligatoire.'
        ]);

        $transfert = TransfertStock::find($id);

        if (!$transfert) {
            return response()->json([
                'success' => false,
                'message' => 'Transfert de stock introuvable.'
            ], 404);
        }

        if (in_array($transfert->status, ['validated', 'cancelled', 'rejected'])) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de rejeter un transfert déjà traité.'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $transfert->update([
                'status'           => 'rejected',
                'rejected_by'      => auth()->id(),
                'rejected_at'      => Carbon::now(),
                'rejection_reason' => $request->reason,
                'updated_by'       => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transfert de stock rejeté avec succès.',
                'data'    => $transfert->load('rejecter')
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors du rejet : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     * @permission TransfertStockController::validateTransfert
     * @permission_desc Valider un transfert de stocks
     */
    public function validateTransfert(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe incorrect.'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $transfert = TransfertStock::with('items')->findOrFail($id);

            if ($transfert->status === 'validated') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Ce transfert a déjà été validé.'
                ], 422);
            }

            foreach ($transfert->items as $item) {
                $lotSource = LotProduit::where('id', $item->lot_produit_id)
                    ->where('id_emplacement', $transfert->emplacement_source_id)
                    ->lockForUpdate()
                    ->first();

                if (!$lotSource || $lotSource->quantite_actuelle < $item->quantite) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuffisant à la source pour le lot " . ($lotSource->numero_lot_fabricant ?? 'sélectionné') . "."
                    ], 422);
                }

                $lotSource->decrement('quantite_actuelle', $item->quantite);

                $lotDestination = LotProduit::where('id_emplacement', $transfert->emplacement_destination_id)
                    ->where('numero_lot_fabricant', $lotSource->numero_lot_fabricant)
                    ->where('id_produit', $lotSource->id_produit)
                    ->lockForUpdate()
                    ->first();

                if ($lotDestination) {
                    $lotDestination->increment('quantite_actuelle', $item->quantite);
                } else {
                    LotProduit::create([
                        'id_emplacement'       => $transfert->emplacement_destination_id,
                        'id_produit'           => $lotSource->id_produit,
                        'numero_lot_fabricant' => $lotSource->numero_lot_fabricant,
                        'quantite_actuelle'    => $item->quantite,
                        'date_peremption'      => $lotSource->date_peremption ?? null,
                        'date_reception'       => now(),
                        'statut'               => $lotSource->statut ?? 'disponible',
                        'fournisseur_id'       => $lotSource->fournisseur_id ?? null,
                        'created_by'           => $user->id,
                        'updated_by'           => $user->id,
                    ]);
                }
            }

            $transfert->update([
                'status'       => 'validated',
                'validated_by' => $user->id,
                'validated_at' => now(),
                'updated_by'   => $user->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transfert validé avec succès. Les stocks ont été mis à jour.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation du transfert : ' . $e->getMessage()
            ], 500);
        }
    }

}
