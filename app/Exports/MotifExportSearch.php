<?php

namespace App\Exports;

use App\Models\OpsTbl_Motif_consultation;
use Illuminate\Support\Collection; // ✅ Utiliser la bonne classe Collection
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MotifExportSearch implements FromCollection, WithHeadings
{
    protected Collection $motifs;

    public function __construct(Collection $motifs)
    {
        $this->motifs = $motifs;
    }

    public function collection()
    {
        if ($this->motifs->isEmpty()) {
            throw new \Exception('Aucune donnée à exporter');
        }

        return $this->motifs->map(function ($motif) {
            return [
                'ID' => $motif->id,
                'Code' => $motif->code,
                'Description' => $motif->description,
                'Dossier Consultation ID' => $motif->dossierConsultation->id ?? '',
                'Dossier Consultation Code' => $motif->dossierConsultation->code ?? '',
                'Rendez-vous ID' => $motif->dossierConsultation->rendezVous->id ?? '',
                'Rendez-vous Code' => $motif->dossierConsultation->rendezVous->code ?? '',
                'Client ID' => $motif->dossierConsultation->rendezVous->client->id ?? '',
                'Client Nom Complet' => $motif->dossierConsultation->rendezVous->client->nomcomplet_client ?? '',
                'Client Référence' => $motif->dossierConsultation->rendezVous->client->ref_cli ?? '',
                'Créé Par (User ID)' => $motif->creator->id ?? '',
                'Créé Par (Login)' => $motif->creator->login ?? '',
                'Mis à jour Par (User ID)' => $motif->updater->id ?? '',
                'Mis à jour Par (Login)' => $motif->updater->login ?? '',
                'Date Création' => $motif->created_at ? $motif->created_at->format('Y-m-d H:i:s') : '',
                'Date Mise à Jour' => $motif->updated_at ? $motif->updated_at->format('Y-m-d H:i:s') : '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Code',
            'Description',
            'Dossier Consultation ID',
            'Dossier Consultation Code',
            'Rendez-vous ID',
            'Rendez-vous Code',
            'Client ID',
            'Client Nom Complet',
            'Client Référence',
            'Créé Par (User ID)',
            'Créé Par (Login)',
            'Mis à jour Par (User ID)',
            'Mis à jour Par (Login)',
            'Date Création',
            'Date Mise à Jour',
        ];
    }
}
