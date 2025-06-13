<?php

namespace App\Exports;
use App\Models\DossierConsultation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
class DossierConsultationExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $dossiers = DossierConsultation::with([
            'creator:id,login',
            'updater:id,login',
            'facture:id,code',
            'rendezVous:id,code,client_id',
            'rendezVous.client:id,nomcomplet_client'
        ])
            ->where('is_deleted', false)
            ->get();

        if ($dossiers->isEmpty()) {
            throw new \Exception('Aucune donnée à exporter');
        }

        return $dossiers->map(function ($dossier) {
            return [
                'ID' => $dossier->id,
                'Code' => $dossier->code,
                'Client' => optional($dossier->rendezVous->client)->nomcomplet_client ?? 'N/A',
                'Poids' => $dossier->poids ?? 'N/A',
                'Tension' => $dossier->tension ?? 'N/A',
                'Taille' => $dossier->taille ?? 'N/A',
                'Saturation' => $dossier->saturation ?? 'N/A',
                'Température' => $dossier->temperature ?? 'N/A',
                'Fréquence cardiaque' => $dossier->frequence_cardiaque ?? 'N/A',
                'Facture' => optional($dossier->facture)->code ?? 'N/A',
                'Rendez-vous' => optional($dossier->rendezVous)->code ?? 'N/A',
                'Créé par' => optional($dossier->creator)->login ?? 'N/A',
                'Modifié par' => optional($dossier->updater)->login ?? 'N/A',
                'Date de création' => $dossier->created_at ? $dossier->created_at->format('Y-m-d H:i') : 'N/A',
            ];
        });
    }

    /**
     * Titres des colonnes
     */
    public function headings(): array
    {
        return [
            'ID',
            'Code',
            'Client',
            'Poids',
            'Tension',
            'Taille',
            'Saturation',
            'Température',
            'Fréquence cardiaque',
            'Facture',
            'Rendez-vous',
            'Créé par',
            'Modifié par',
            'Date de création',
        ];
    }
}
