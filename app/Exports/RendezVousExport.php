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
        $rendezVous = RendezVous::with(["parent", "children", "prestation", "client", "consultant", "createdBy", "updatedBy"])->get();

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
            $rendezVous->code,
            $rendezVous->prestation?->type_label,
            $rendezVous->client?->nomcomplet_client ?? '',
            $rendezVous->consultant?->nomcomplet ?? '',
            $rendezVous->createdBy?->nom_utilisateur ?? '',
            $rendezVous->updatedBy?->nom_utilisateur ?? '',
            $rendezVous->dateheure_rdv ? date('d/m/Y H:i', strtotime($rendezVous->dateheure_rdv)) : '',
            $rendezVous->details ?? '',
            $rendezVous->nombre_jour_validite ?? 'N/A',
            $rendezVous->type ?? 'N/A',
            $rendezVous->etat ?? 'N/A',
            $rendezVous->created_at?->format('d/m/Y H:i:s') ?? '',
            $rendezVous->updated_at?->format('d/m/Y H:i:s') ?? '',
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
            'Code',
            'Type prestation',
            'Client',
            'Consultant',
            'Créé par',
            'Modifié par',
            'Date RDV',
            'Détails',
            'Nombre de jours validité',
            'Type',
            'État',
            'Date de création',
            'Date de mise à jour',
        ];
    }
}
