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
            'emplacement',
            'creator:id,nom_utilisateur',
            'updater:id,nom_utilisateur',
            'rendezVous',
            'rendezVous.client',
            'rendezVous.consultant:id,nomcomplet,ref',
            'medias',
            'rendezVous.prestation',
        ])->get();

        if ($dossiers->isEmpty()) {
            throw new \Exception('Aucune donnée à exporter');
        }

        return $dossiers->map(function ($dossier) {
            return [
                $dossier->id,
                $dossier->code,
                optional($dossier->rendezVous->prestation)->type_label ?? '',
                optional($dossier->rendezVous->client)->nomcomplet_client ?? '',
                optional($dossier->rendezVous->consultant)->nomcomplet ?? '',
                optional($dossier->emplacement)->name ?? '',
                $dossier->poids ?? '',
                $dossier->taille ?? '',
                $dossier->tension ?? '',
                $dossier->tension_arterielle_bg ?? '',
                $dossier->tension_arterielle_bd ?? '',
                $dossier->saturation ?? '',
                $dossier->temperature ?? '',
                $dossier->frequence_cardiaque ?? '',
                optional($dossier->facture)->code ?? '',
                optional($dossier->rendezVous)->code ?? '',
                optional($dossier->rendezVous)->etat ?? '',
                optional($dossier->creator)->nom_utilisateur ?? '',
                optional($dossier->updater)->nom_utilisateur ?? '',
                $dossier->created_at ? $dossier->created_at->format('Y-m-d H:i') : '',
                $dossier->updated_at ? $dossier->updated_at->format('Y-m-d H:i') : '',
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
            'Type prestation',
            'Client',
            'Consultant',
            'Emplacement',
            'Poids',
            'Taille',
            'Tension',
            'Tension artérielle BG',
            'Tension artérielle BD',
            'Saturation',
            'Température',
            'Fréquence cardiaque',
            'Facture',
            'Rendez-vous',
            'Etat',
            'Créé par',
            'Modifié par',
            'Date de création',
            'Date de mise à jour',
        ];
    }
}
