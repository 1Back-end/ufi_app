<?php

namespace App\Exports;

use App\Models\Examen;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExamenExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $examens = Examen::with([
            'tubePrelevement',
            'typePrelevement',
            'paillasse',
            'subFamilyExam',
            'kbPrelevement'
        ])
            ->orderBy('name', 'asc')
            ->get();

        if ($examens->isEmpty()) {
            throw new \Exception('Aucune donnée à exporter');
        }

        return $examens->map(function ($examen, $index) {
            return [
                'N°' => $index + 1,
                'Code' => $examen->code,
                'Nom' => $examen->name,
                'Nom 1' => $examen->name1,
                'Nom 2' => $examen->name2,
                'Nom 3' => $examen->name3,
                'Nom 4' => $examen->name4,
                'Nom Abrégé' => $examen->name_abrege,
                'Prix' => $examen->price,
                'B' => $examen->b,
                'B1' => $examen->b1,
                'Délai (min)' => $examen->renderer_duration,
                'Unité Prélèvement' => $examen->prelevement_unit,
                'Utilisé pour commission' => $examen->is_used_for_commission ? 'Oui' : 'Non',
                'Paillasse' => $examen->paillasse?->name ?? 'N/A',
                'Sous-Famille' => $examen->subFamilyExam?->name ?? 'N/A',
                'Tube de prélèvement' => $examen->tubePrelevement?->name ?? 'N/A',
                'Type de prélèvement' => $examen->typePrelevement?->name ?? 'N/A',
                'KB Prélèvement' => $examen->kbPrelevement?->name ?? 'N/A',
                'Nombre d\'éléments' => $examen->element_paillasses_count,
                'Créé le' => $examen->created_at?->format('Y-m-d H:i:s'),
                'Mis à jour le' => $examen->updated_at?->format('Y-m-d H:i:s'),
            ];
        });
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'N°',
            'Code',
            'Nom',
            'Nom 1',
            'Nom 2',
            'Nom 3',
            'Nom 4',
            'Nom Abrégé',
            'Prix',
            'B',
            'B1',
            'Délai (min)',
            'Unité Prélèvement',
            'Utilisé pour commission',
            'Paillasse',
            'Sous-Famille',
            'Tube de prélèvement',
            'Type de prélèvement',
            'KB Prélèvement',
            'Nombre d\'éléments',
            'Créé le',
            'Mis à jour le',
        ];
    }
}
