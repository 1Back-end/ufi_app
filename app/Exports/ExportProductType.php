<?php

namespace App\Exports;

use App\Models\ProductType;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExportProductType implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return ProductType::with(['creator', 'updater'])->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Nom',
            'Forme galénique',
            'Forme générique',
            'Conditionnement',
            'Dosage',
            'Statut',
            'Créé par',
            'Mis à jour par',
            'Date de création',
        ];
    }

    /**
     * @param mixed $productType
     * @return array
     */
    public function map($productType): array
    {
        return [
            $productType->id,
            $productType->name,
            $productType->accepts_galenic_form ? 'Oui' : 'Non',
            $productType->accepts_generic_form ? 'Oui' : 'Non',
            $productType->accepts_packaging ? 'Oui' : 'Non',
            $productType->accepts_dosage ? 'Oui' : 'Non',
            $productType->is_active ? 'Actif' : 'Inactif',
            $productType->creator ? $productType->creator->nom_utilisateur : '',
            $productType->updater ? $productType->updater->nom_utilisateur : '',
            $productType->created_at ? $productType->created_at->format('d/m/Y H:i') : '',
        ];
    }
}
