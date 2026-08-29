<?php

namespace App\Exports;

use App\Models\Packaging;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExportPackaging implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Packaging::with(['creator', 'updater'])->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Code',
            'Nom',
            'Quantité',
            'Statut',
            'Créé par',
            'Mis à jour par',
            'Date de création',
        ];
    }

    /**
     * @param mixed $packaging
     * @return array
     */
    public function map($packaging): array
    {
        return [
            $packaging->id,
            $packaging->code,
            $packaging->name,
            $packaging->quantity,
            $packaging->is_active ? 'Actif' : 'Inactif',
            $packaging->creator ? $packaging->creator->nom_utilisateur : '-',
            $packaging->updater ? $packaging->updater->nom_utilisateur : '-',
            $packaging->created_at ? $packaging->created_at->format('d/m/Y H:i') : '',
        ];
    }
}
