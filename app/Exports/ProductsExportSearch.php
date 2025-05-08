<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExportSearch implements FromCollection, WithHeadings, WithMapping
{
    protected $products;

    // Le constructeur prend un tableau de produits comme argument
    public function __construct($products)
    {
        $this->products = $products;
    }

    // La méthode collection retourne les produits passés dans le constructeur
    public function collection()
    {
        return collect($this->products);
    }

    // Définition des entêtes pour l'export
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

    // Fonction de mappage des produits dans l'export
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
