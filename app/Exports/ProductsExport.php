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
        return Product::with([
            'fournisseurs',
            'creator',
            'updater',
            'productType',
            'packagings',
            'dosages'
        ])->get();
    }

    public function headings(): array
    {
        return [
            'Référence',
            'Nom commercial',
            'Prix de vente',
            'Prix d\'achat',
            'Prix en pharmacie',
            'Conditionnement(s)',
            'Dosage(s) associé(s)',
            'Fournisseur(s)',
            'Qté en stock',
            'Type',
            'Facturable',
            'Suspendu',
            'Épuisé',
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
            $product->ref ?? '',
            $product->name ?? '',
            $product->price ?? 0,
            $product->purchase_price ?? 0,
            $product->pharmacy_price ?? 0,
            $product->packagings->pluck('name')->join(', ') ?: '',
            $product->dosages->pluck('name')->join(', ') ?: '',
            $product->fournisseurs->pluck('full_name')->join(', ') ?: '',
            $product->total_stock ?? 0,
            optional($product->productType)->name ?? '',
            $product->facturable ? 'Oui' : 'Non',
            $product->is_suspended ? 'Oui' : 'Non',
            $product->is_out_of_stock ? 'Oui' : 'Non',
            $product->status ?? '',
            $product->created_at?->format('d/m/Y H:i:s') ?? '',
            $product->creator?->nom_utilisateur ?? 'N/A',
            $product->updated_at?->format('d/m/Y H:i:s') ?? '',
            $product->updater?->nom_utilisateur ?? 'N/A',
        ];
    }
}
