<?php

namespace App\Exports;

use App\Models\OpsTblRapportConsultation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OpsTblRapportConsultationsExport implements FromCollection, WithMapping, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $reports = OpsTblRapportConsultation::with([
            'creator',
            'updater',
            'dossierConsultation.rendezVous.client',
            'dossierConsultation.rendezVous.consultant'
        ])->get();

        if ($reports->isEmpty()) {
            throw new \Exception('Aucune donnée à exporter');
        }

        return $reports;
    }

    /**
     * @param mixed $report
     * @return array
     */
    public function map($report): array
    {
        $rendezVous = $report->dossierConsultation?->rendezVous;

        return [
            $report->id,
            $report->code,
            $report->dossierConsultation?->code ?? '',
            $report->dossierConsultation?->created_at?->format('d/m/Y H:i') ?? '',
            $rendezVous?->code ?? '',
            $rendezVous?->dateheure_rdv ? $rendezVous->dateheure_rdv->format('d/m/Y H:i') : '',
            $rendezVous?->client?->nomcomplet_client ?? '',
            $rendezVous?->consultant?->nomcomplet ?? '',
            $report->creator?->nom_utilisateur ?? '',
            $report->updater?->nom_utilisateur ?? '',
            $report->resume ?? '',
            $report->conclusion ?? '',
            $report->recommandations ?? '',
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
            'Code Rapport',
            'Dossier N°',
            'Date Dossier',
            'Rendez-Vous N°',
            'Date Rendez-Vous',
            'Client',
            'Consultant',
            'Créé par',
            'Modifié par',
            'Résumé',
            'Conclusion',
            'Recommandations',
        ];
    }
}
