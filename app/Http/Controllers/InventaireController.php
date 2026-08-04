<?php

namespace App\Http\Controllers;

use App\Models\Inventaire;
use App\Models\InventaireItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InventaireController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = Inventaire::with(['centre', 'creator', 'updater', 'items.product', 'items.emplacement']);

        if ($request->filled('date')) {
            $date = \Carbon\Carbon::parse($request->input('date'));
            $query->whereBetween('created_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()]);
        }

        if ($search = trim($request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('commentaires', 'like', "%{$search}%");
            });
        }

        $data = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'message'      => 'Liste des inventaires récupérée avec succès',
            'data'         => $data->items(),
            'current_page' => $data->currentPage(),
            'last_page'    => $data->lastPage(),
            'total'        => $data->total(),
        ], 200);
    }


    public function store(Request $request)
    {
        $centreId = $request->header('centre');

        if (!$centreId) {
            return response()->json([
                'message' => 'Centre non fourni'
            ], 400);
        }

        $request->validate([
            'date_inventaire'   => 'nullable|date',
            'commentaires'      => 'nullable|string',
            'items'             => 'required|array|min:1',
            'items.*.product_id'        => 'required|exists:products,id',
            'items.*.emplacement_id'    => 'nullable|exists:emplacements_products,id',
            'items.*.quantity_in_stock' => 'required|integer|min:0',
            'items.*.quantity_observed' => 'required|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            $reference = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

            $inventaire = Inventaire::create([
                'centre_id'       => $centreId,
                'reference'       => $reference,
                'date_inventaire' => $request->input('date_inventaire') ?? now(),
                'status'          => $request->input('status', 'brouillon'),
                'commentaires'    => $request->commentaires,
                'created_by'      => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                $qStock = $item['quantity_in_stock'];
                $qObserved = $item['quantity_observed'];
                $ecart = $qObserved - $qStock;

                InventaireItem::create([
                    'inventaire_id'     => $inventaire->id,
                    'product_id'        => $item['product_id'],
                    'emplacement_id'    => $item['emplacement_id'] ?? null,
                    'quantity_in_stock' => $qStock,
                    'quantity_observed' => $qObserved,
                    'ecart'             => $ecart,
                    'created_by'        => auth()->id(),
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Inventaire enregistré avec succès',
                'data'    => $inventaire->load('items.product', 'items.emplacement')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de l’enregistrement de l’inventaire',
                'error'   => $e->getMessage()
            ], 500);
        }
    }



    public function update(Request $request, $id)
    {
        $centreId = $request->header('centre');

        if (!$centreId) {
            return response()->json([
                'message' => 'Centre non fourni'
            ], 400);
        }

        $inventaire = Inventaire::where('id', $id)->first();

        if (!$inventaire) {
            return response()->json([
                'message' => 'Inventaire introuvable'
            ], 404);
        }

        if ($inventaire->status === 'valide') {
            return response()->json([
                'message' => 'Impossible de modifier un inventaire déjà validé'
            ], 403);
        }

        $request->validate([
            'date_inventaire'           => 'nullable|date',
            'commentaires'              => 'nullable|string',
            'status'                    => 'nullable|string',
            'items'                     => 'required|array|min:1',
            'items.*.product_id'        => 'required|exists:products,id',
            'items.*.emplacement_id'    => 'nullable|exists:emplacements_products,id',
            'items.*.quantity_in_stock' => 'required|integer|min:0',
            'items.*.quantity_observed' => 'required|integer|min:0',
        ]);

        try {
            DB::beginTransaction();


            $inventaire->update([
                'date_inventaire' => $request->input('date_inventaire', $inventaire->date_inventaire),
                'status'          => $request->input('status', $inventaire->status),
                'commentaires'    => $request->commentaires,
                'updated_by'      => auth()->id(),
            ]);

            $inventaire->items()->delete();


            foreach ($request->items as $item) {
                $qStock = $item['quantity_in_stock'];
                $qObserved = $item['quantity_observed'];
                $ecart = $qObserved - $qStock;

                InventaireItem::create([
                    'inventaire_id'     => $inventaire->id,
                    'product_id'        => $item['product_id'],
                    'emplacement_id'    => $item['emplacement_id'] ?? null,
                    'quantity_in_stock' => $qStock,
                    'quantity_observed' => $qObserved,
                    'ecart'             => $ecart,
                    'created_by'        => clone $inventaire->created_by ?? auth()->id(),
                    'updated_by'        => auth()->id(),
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Inventaire mis à jour avec succès',
                'data'    => $inventaire->load('items.product', 'items.emplacement')
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la mise à jour de l’inventaire',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $inventaire = Inventaire::with(['centre', 'creator', 'updater', 'items.product', 'items.emplacement'])
            ->find($id);

        if (!$inventaire) {
            return response()->json([
                'message' => 'Inventaire introuvable'
            ], 404);
        }

        return response()->json([
            'message' => 'Détails de l’inventaire récupérés avec succès',
            'data'    => $inventaire
        ], 200);
    }

    public function destroy($id)
    {
        $inventaire = Inventaire::find($id);

        if (!$inventaire) {
            return response()->json([
                'message' => 'Inventaire introuvable'
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Suppression des items liés (si pas de cascade onDelete en base)
            $inventaire->items()->delete();
            $inventaire->delete();

            DB::commit();

            return response()->json([
                'message' => 'Inventaire supprimé avec succès'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la suppression',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
