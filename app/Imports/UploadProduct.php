<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Fournisseurs;
use App\Models\Packaging;
use App\Models\EmplacementsProduct;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Auth;

class UploadProduct implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
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

        $reference = !empty($row['reference'])
            ? trim($row['reference'])
            : 'PRD-' . strtoupper(Str::random(8));

        $product = Product::firstOrCreate(
            ['ref' => $reference],
            [
                'name' => trim($row['designation']),
                'laboratory_family' => trim($row['famille']) ?? null,
                'fabricant' => trim($row['fabricant']) ?? null,
                'is_active' => true,
                'created_by' => Auth::id()
            ]
        );

        if (!empty($row['fabricant']) && $product->fabricant !== trim($row['fabricant'])) {
            $product->update(['fabricant' => trim($row['fabricant'])]);
        }


        if ($fournisseurId && !$product->fournisseurs()->where('fournisseur_id', $fournisseurId)->exists()) {
            $product->fournisseurs()->attach($fournisseurId);
        }

        if (!empty($row['conditionnement'])) {
            $packaging = Packaging::firstOrCreate(
                ['name' => trim($row['conditionnement'])],
                [
                    'is_active' => true,
                    'created_by' => Auth::id()
                ]
            );

            if (!$product->packagings()->where('packaging_product_id', $packaging->id)->exists()) {
                $product->packagings()->attach($packaging->id, [
                    'is_default' => true,
                    'created_by' => Auth::id()
                ]);
            }
        }

        if (!empty($row['emplacement'])) {
            EmplacementsProduct::firstOrCreate(
                [
                    'zone_stockage' => trim($row['emplacement'])
                ],
                [
                    'is_active' => true,
                    'is_primary' => false,
                    'created_by' => Auth::id()
                ]
            );
        }

        return $product;
    }
}
