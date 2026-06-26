<?php

namespace App\Http\Controllers;

use App\Models\LotProduit;
use Illuminate\Http\Request;

class LotProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('limit', 25);
        $page = $request->input('page', 1);

        $query = LotProduit::with(["creator", "updater", "produit","emplacement"]);

        if ($search = trim($request->input('search'))) {
            $query->where(function ($query) use ($search) {

                $query->where('id', 'like', "%{$search}%")
                    ->orWhere('numero_lot_fabricant', 'like', "%{$search}%")
                    ->orWhere('date_peremption', 'like', "%{$search}%")
                    ->orWhere('date_reception', 'like', "%{$search}%")
                    ->orWhere('quantite_actuelle', 'like', "%{$search}%")
                    ->orWhere('statut', 'like', "%{$search}%")

                    ->orWhereHas('produit', function ($q) use ($search) {
                        $q->where('ref', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%")
                            ->orWhere('generic_name', 'like', "%{$search}%")
                            ->orWhere('manufacturer_reference', 'like', "%{$search}%")
                            ->orWhere('product_type', 'like', "%{$search}%")
                            ->orWhere('dosage', 'like', "%{$search}%")
                            ->orWhere('laboratory_family', 'like', "%{$search}%")
                            ->orWhere('storage_unit', 'like', "%{$search}%")
                            ->orWhere('consumption_unit', 'like', "%{$search}%")
                            ->orWhere('conversion_factor', 'like', "%{$search}%")
                            ->orWhere('alert_threshold', 'like', "%{$search}%")
                            ->orWhere('minimum_threshold', 'like', "%{$search}%")
                            ->orWhere('storage_temperature', 'like', "%{$search}%")
                            ->orWhere('purchase_price', 'like', "%{$search}%")
                            ->orWhere('price', 'like', "%{$search}%")
                            ->orWhere('facturable', 'like', "%{$search}%");
                    })

                    ->orWhereHas('emplacement', function ($q) use ($search) {
                        $q->where('zone_stockage', 'like', "%{$search}%")
                            ->orWhere('equipement', 'like', "%{$search}%")
                            ->orWhere('position_detaillee', 'like', "%{$search}%")
                            ->orWhere('is_active', 'like', "%{$search}%");
                    });
            });
        }

        $lots = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $lots->items(),
            'current_page' => $lots->currentPage(),
            'last_page' => $lots->lastPage(),
            'total' => $lots->total(),
        ]);
    }
}
