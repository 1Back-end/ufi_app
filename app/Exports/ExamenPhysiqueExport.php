<?php

namespace App\Exports;

use App\Models\OpsTbl_Examen_Physique;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExamenPhysiqueExport implements FromCollection, WithHeadings
{
    /**
     * Retourne les données à exporter
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection(): Collection
    {
        $examen_physiques = OpsTbl_Examen_Physique::with([
            'creator:id,login',
            'updater:id,login',
            'categorieExamenPhysique:id,name',
            'motifConsultation:id,libelle,code,description,dossier_consultation_id',
            'motifConsultation.dossierConsultation:id,code'
        ])
            ->where('is_deleted', false)
            ->get();

        if ($examen_physiques->isEmpty()) {
            throw new \Exception('Aucune donnée à exporter');
        }

        return $examen_physiques->map(function ($item) {
            return [
                'Code' => $item->code,
                'Libellé' => $item->libelle,
                'Résultat' => $item->resultat,
                'Catégorie' => $item->categorieExamenPhysique->name ?? '',
                'Motif de consultation' => $item->motifConsultation->libelle ?? '',
                'Code motif' => $item->motifConsultation->code ?? '',
                'Code dossier' => $item->motifConsultation->dossierConsultation->code ?? '',
                'Créé par' => $item->creator->login ?? '',
                'Modifié par' => $item->updater->login ?? '',
                'Date de création' => $item->created_at?->format('Y-m-d H:i:s'),
                'Date de modification' => $item->updated_at?->format('Y-m-d H:i:s'),
            ];
        });
    }

    /**
     * Retourne les entêtes du fichier Excel
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'Code',
            'Libellé',
            'Résultat',
            'Catégorie',
            'Motif de consultation',
            'Code motif',
            'Code dossier',
            'Créé par',
            'Modifié par',
            'Date de création',
            'Date de modification'
        ];
    }
}
