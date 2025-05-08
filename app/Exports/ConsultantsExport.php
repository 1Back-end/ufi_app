<?php

namespace App\Exports;

use App\Models\Consultant;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Carbon\Carbon;

class ConsultantsExport implements FromCollection, WithHeadings
{
    /**
     * Récupère les données des consultants pour l'exportation
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $consultants = Consultant::with(['code_specialite', 'code_titre', 'code_service_hopi', 'creator', 'updater'])
            ->where('is_deleted', false)
            ->get();

        if ($consultants->isEmpty()) {
            throw new \Exception('Aucune donnée à exporter');
        }

        return $consultants->map(function ($consultant) {
            return [
                '#' => $consultant->id ?? 'N/A',
                'Référence' => $consultant->ref ?? 'N/A',
                'Nom' => $consultant->nom ?? 'N/A',
                'Prénom' => $consultant->prenom ?? 'N/A',
                'Email' => $consultant->email ?? 'N/A',
                'Téléphone Principal' => $consultant->tel ?? 'N/A',
                'Téléphone Secondaire' => $consultant->tel1 ?? 'N/A',
                'Nom Complet' => $consultant->nomcomplet ?? 'N/A',
                'Type Consultant' => $consultant->type ?? 'N/A',
                'Statut Consultant' => $consultant->status ?? 'N/A',
                'Disponible sur WhatsApp' => $consultant->TelWhatsApp ?? 'N/A',
                'Créé le' => optional($consultant->created_at)->format('d/m/Y H:i:s') ?? 'N/A',
                'Par' => optional($consultant->creator)->email ?? 'N/A',
                'Modifié le' => optional($consultant->updated_at)->format('d/m/Y H:i:s') ?? 'N/A',
                'Par (modif)' => optional($consultant->updater)->email ?? 'N/A',
            ];
        });




}

    /**
     * Définit les en-têtes de l'export
     * @return array
     */
    public function headings(): array
    {
        return [
            '#',
            'Référence',
            'Nom',
            'Prénom',
            'Email',
            'Téléphone Principal',
            'Téléphone Secondaire',
            'Nom Complet',
            'Type Consultant',
            'Statut Consultant',
            'Disponible sur WhatsApp',
            'Créé le',
            'Par',
            'Modifié le',
            'Par (modif)',
        ];
    }
}
