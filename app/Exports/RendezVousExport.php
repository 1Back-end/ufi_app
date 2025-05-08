<?php

namespace App\Exports;

use App\Models\RendezVous;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class RendezVousExport implements FromCollection, WithHeadings, WithStrictNullComparison, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $rendezVous = RendezVous::where('is_deleted', false)->get();

        if ($rendezVous->isEmpty()) {
            throw new \Exception('Aucune donnée à exporter');
        }

        return $rendezVous;
    }

    /**
     * Mappe chaque ligne du fichier exporté.
     *
     * @param \App\Models\RendezVous $rendezVous
     * @return array
     */
    public function map($rendezVous): array
    {
        return [
            $rendezVous->id,
            $rendezVous->client?->nomcomplet_client ?? 'N/A',
            $rendezVous->consultant?->nomcomplet ?? 'N/A',
            $rendezVous->created_at?->format('d/m/Y H:i:s') ?? 'N/A',
            $rendezVous->createdBy?->email ?? 'N/A',
            $rendezVous->updated_at?->format('d/m/Y H:i:s') ?? 'N/A',
            $rendezVous->updatedBy?->email ?? 'N/A',
            $rendezVous->date_emission ? date('d/m/Y H:i:s', strtotime($rendezVous->date_emission)) : 'N/A',
            $rendezVous->dateheure_rdv ? date('d/m/Y', strtotime($rendezVous->dateheure_rdv)) : 'N/A',
            $rendezVous->heure_debut ? date('H:i', strtotime($rendezVous->heure_debut)) : 'N/A',
            $rendezVous->heure_fin ? date('H:i', strtotime($rendezVous->heure_fin)) : 'N/A',
            $rendezVous->details ?? 'N/A',
            $rendezVous->nombre_jour_validite ?? 'N/A',
            $rendezVous->type ?? 'N/A',
            $rendezVous->etat ?? 'N/A',
            $rendezVous->code ?? 'N/A',
        ];
    }

    /**
     * Définit les en-têtes du fichier Excel.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Client',
            'Consultant',
            'Créé le',
            'Par',
            'Modifié le',
            'Par (modif)',
            'Date d\'émission',
            'Date RDV',
            'Heure Début',
            'Heure Fin',
            'Détails',
            'Nombre de jours validité',
            'Type',
            'État',
            'Code',
        ];
    }
}
