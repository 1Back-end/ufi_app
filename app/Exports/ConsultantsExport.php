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
        $consultants = Consultant::with([
            'code_hopi',
            'specialite',
            'code_titre',
            'disponibilites',
            'user',
            'creator',
            'updater',
            'account',
            'prestations.prestationType',
        ])->get();

        if ($consultants->isEmpty()) {
            throw new \Exception('Aucune donnée à exporter');
        }

        return $consultants->map(function ($consultant) {
            $commissionsSummary = $consultant->prestations->map(function ($share) {
                $categoryName = optional($share->prestationType)->name ?? '';
                return "{$categoryName}: {$share->share_rate}% ({$share->calculation_type})";
            })->implode(' | ');

            return [
                '#' => $consultant->id ?? '',
                'Référence' => $consultant->ref ?? '',
                'Nom' => $consultant->nom ?? '',
                'Prénom' => $consultant->prenom ?? '',
                'Email' => $consultant->email ?? '',
                'Téléphone Principal' => $consultant->tel ?? '',
                'Téléphone Secondaire' => $consultant->tel1 ?? '',
                'Nom Complet' => $consultant->nomcomplet ?? '',
                'Type Consultant' => $consultant->type ?? '',
                'Actif' => $consultant->is_active ? 'Oui' : 'Non',
                'Archivé' => $consultant->is_archived ? 'Oui' : 'Non',
                'Compte de Paiement' => optional($consultant->account)->name ?? '',
                'Commissions' => $commissionsSummary ?: 'Aucune',
                'Disponible sur WhatsApp' => $consultant->TelWhatsApp ?? '',
                'Créé le' => optional($consultant->created_at)->format('d/m/Y H:i:s') ?? '',
                'Par' => optional($consultant->creator)->email ?? '',
                'Modifié le' => optional($consultant->updated_at)->format('d/m/Y H:i:s') ?? '',
                'Par (modif)' => optional($consultant->updater)->email ?? '',
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
            'Actif',
            'Archivé',
            'Compte de Paiement',
            'Commissions',
            'Disponible sur WhatsApp',
            'Créé le',
            'Par',
            'Modifié le',
            'Par (modif)',
        ];
    }
}
