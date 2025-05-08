<?php
namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Product::with(['fournisseurs', 'categories', 'uniteProduit', 'groupProduct', 'voieTransmission','creator','updater'])->get();
    }

    public function headings(): array
    {
        return [
            'Référence',
            'Nom',
            'Dosage',
            'Prix',
            'Voie de transmission',
            'Unité de produit',
            'Groupe de produits',
            'Catégories',
            'Fournisseurs',
            'Unité/Emballage',
            'Condition/Unité Emballage',
            'Dosage défaut',
            'Schéma administration',
            'Statut',
            'Créé le',
            'Par',
            'Modifié le',
            'Par (modif)',
        ];
    }

    public function map($product): array
    {
        return [
            $product->ref,
            $product->name,
            $product->dosage,
            $product->price,
            optional($product->voieTransmission)->name,
            optional($product->uniteProduit)->name,
            optional($product->groupProduct)->name,
            $product->categories->pluck('name')->join(', '),
            $product->fournisseurs->pluck('nom')->join(', '),
            $product->unite_par_emballage,
            $product->condition_par_unite_emballage,
            $product->Dosage_defaut,
            $product->schema_administration,
            $product->status,
            $product->created_at?->format('d/m/Y H:i:s') ?? 'N/A',
            $product->creator?->email ?? 'N/A',
            $product->updated_at?->format('d/m/Y H:i:s') ?? 'N/A',
            $product->updater?->email ?? 'N/A',
        ];
    }
}

