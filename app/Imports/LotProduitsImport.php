<?php

namespace App\Imports;

use App\Models\Centre;
use App\Models\EmplacementsProduct;
use App\Models\Fournisseurs;
use App\Models\LotProduit;
use App\Models\LotProduitConditionnement;
use App\Models\Packaging;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LotProduitsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // 1. Gestion du Fournisseur
        $fournisseurId = null;
        if (!empty($row['fournisseur'])) {
            $fournisseur = Fournisseurs::firstOrCreate(
                ['full_name' => mb_strtoupper(trim($row['fournisseur']), 'UTF-8')],
                [
                    'company_name' => trim($row['fournisseur']),
                    'is_active' => true,
                    'created_by' => Auth::id()
                ]
            );
            $fournisseurId = $fournisseur->id;
        }

        // 2. Gestion de la Référence et du Produit
        $reference = !empty($row['reference'])
            ? trim($row['reference'])
            : 'PRD-' . strtoupper(Str::random(8));

        $centre = Centre::where('used_for_product_gt_labo', true)->first();
        $centreId = $centre ? $centre->id : null;

        $product = Product::firstOrCreate(
            ['ref' => $reference],
            [
                'name' => trim($row['nom_du_produit'] ?? 'Produit sans nom'),
                'fabricant' => trim($row['fabricant']) ?? null,
                'is_active' => true,
                'facturable' => false,
                'centre_id' => $centreId,
                'created_by' => Auth::id()
            ]
        );

        $updateData = [];
        $updateData['facturable'] = false;
        if (empty($product->centre_id) && $centreId) {
            $updateData['centre_id'] = $centreId;
        }

        if (!empty($row['fabricant']) && $product->fabricant !== trim($row['fabricant'])) {
            $updateData['fabricant'] = trim($row['fabricant']);
        }

        if (Schema::hasColumn('products', 'used_for_product_gt_labo')) {
            $updateData['used_for_product_gt_labo'] = true;
        }

        if (!empty($updateData)) {
            $product->update($updateData);
        }

        if ($fournisseurId && !$product->fournisseurs()->where('fournisseur_id', $fournisseurId)->exists()) {
            $product->fournisseurs()->attach($fournisseurId);
        }

        $packagingId = null;
        if (!empty($row['conditionnement'])) {
            $packaging = Packaging::firstOrCreate(
                ['name' => trim($row['conditionnement'])],
                [
                    'is_active' => true,
                    'created_by' => Auth::id()
                ]
            );
            $packagingId = $packaging->id;

            if (!$product->packagings()->where('packaging_product_id', $packagingId)->exists()) {
                $product->packagings()->attach($packagingId, [
                    'is_default' => true,
                    'created_by' => Auth::id()
                ]);
            }
        }

        $zoneStockage = !empty($row['emplacement']) ? trim($row['emplacement']) : 'STOCK GÉNÉRAL';

        $emplacement = EmplacementsProduct::firstOrCreate(
            [
                'zone_stockage' => $zoneStockage
            ],
            [
                'is_active' => true,
                'is_primary' => false,
                'created_by' => Auth::id()
            ]
        );
        $emplacementId = $emplacement->id;

        $numeroLot = !empty($row['numero_de_lot'])
            ? trim($row['numero_de_lot'])
            : 'LOT-' . now()->format('Y-m-d');

        $datePeremption = !empty($row['date_de_peremption'])
            ? Carbon::parse($row['date_de_peremption'])
            : null;

        // Récupération de la quantité sous forme de chaîne de caractères (ex: "6 x 5 ml")
        $quantiteActuelle = isset($row['stock_ponctuel']) ? trim((string)$row['stock_ponctuel']) : '0';

        $lotProduit = LotProduit::updateOrCreate(
            [
                'numero_lot_fabricant' => $numeroLot,
                'id_produit'           => $product->id,
            ],
            [
                'centre_id'            => $centreId,
                'id_emplacement'       => $emplacementId,
                'fournisseur_id'       => $fournisseurId,
                'quantite_actuelle'    => $quantiteActuelle,
                'date_reception'       => Carbon::today(),
                'date_peremption'      => $datePeremption,
                'statut'               => 'Disponible',
                'updated_by'           => Auth::id(),
                'created_by'           => Auth::id(),
            ]
        );

        if ($packagingId) {
            LotProduitConditionnement::updateOrCreate(
                [
                    'lot_produit_id'        => $lotProduit->id,
                    'product_packagings_id' => $packagingId,
                ],
                [
                    'quantite'   => $quantiteActuelle,
                    'price'      => 0,
                    'pt'         => 0,
                    'updated_by' => Auth::id(),
                    'created_by' => Auth::id(),
                ]
            );
        }
        return null;
    }
}
