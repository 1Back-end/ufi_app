<?php

namespace App\Exports;

use App\Models\OpsTblEnquete;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExamenEnqueteExport implements FromCollection, WithHeadings
{
    /**
     * Retourne la collection de données formatée pour l'export.
     */
    public function collection(): Collection
    {
        $examenEnquetes = OpsTblEnquete::with([
            'creator:id,login',
            'updater:id,login',
            'categorieEnquete:id,name',
            'motifConsultation:id,libelle,code,description,dossier_consultation_id',
            'motifConsultation.dossierConsultation:id,code'
        ])
            ->where('is_deleted', false)
            ->get();

        return $examenEnquetes->map(function ($item) {
            return [
                '#' => $item->id,
                'Code' => $item->code,
                'Libellé' => $item->libelle,
                'Résultat' => $item->resultat,
                'Catégorie Enquête' => optional($item->categorieEnquete)->name,
                'Motif Consultation' => optional($item->motifConsultation)->libelle,
                'Code Motif' => optional($item->motifConsultation)->code,
                'Description Motif' => optional($item->motifConsultation)->description,
                'Code Dossier Consultation' => optional($item->motifConsultation?->dossierConsultation)->code,
                'Créé par' => optional($item->creator)->login,
                'Modifié par' => optional($item->updater)->login,
                'Date Création' => $item->created_at?->format('Y-m-d H:i'),
                'Date Modification' => $item->updated_at?->format('Y-m-d H:i'),
            ];
        });
    }

    /**
     * Retourne les en-têtes du fichier Excel.
     */
    public function headings(): array
    {
        return [
            'Code',
            'Libellé',
            'Résultat',
            'Catégorie Enquête',
            'Motif Consultation',
            'Code Motif',
            'Description Motif',
            'Code Dossier Consultation',
            'Créé par',
            'Modifié par',
            'Date Création',
            'Date Modification',
        ];
    }
}
