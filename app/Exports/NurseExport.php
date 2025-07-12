<?php

namespace App\Exports;

use App\Models\Nurse;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class NurseExport implements FromCollection, WithMapping, WithHeadings
{
    protected $nurses;

    public function collection()
    {
        $this->nurses = Nurse::with(['creator', 'editor'])
            ->where('is_deleted', false)
            ->get();

        if ($this->nurses->isEmpty()) {
            throw new \Exception('Aucune donnée à exporter');
        }

        return $this->nurses;
    }

    public function map($nurse): array
    {
        return [
            $nurse->id,
            $nurse->nom,
            $nurse->prenom,
            $nurse->email,
            $nurse->telephone,
            $nurse->matricule,
            $nurse->specialite ?? '-',
            $nurse->adresse ?? '-',
            $nurse->is_active ? 'Actif' : 'Inactif',
            optional($nurse->creator)->email ?? 'N/A',
            optional($nurse->editor)->email ?? 'N/A',
            $nurse->created_at?->format('Y-m-d H:i') ?? '',
            $nurse->updated_at?->format('Y-m-d H:i') ?? '',
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nom',
            'Prénom',
            'Email',
            'Téléphone',
            'Matricule',
            'Spécialité',
            'Adresse',
            'Statut',
            'Créé par',
            'Modifié par',
            'Date de création',
            'Dernière modification',
        ];
    }
}
