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
        $consultants = Consultant::select(
            'id',
            'ref_consult',
            'nomcomplet_consult',
            'email_consul',
            'tel_consult',
            'tel1_consult',
            'type_consult',
            'status_consult',
            'TelWhatsApp',
            'created_at',
            'updated_at'
        )
            ->where('is_deleted', false) // S'assurer que seuls les consultants non supprimés sont retournés
            ->get();

        // Vérification si aucun consultant n'est trouvé
        if ($consultants->isEmpty()) {
            // Si aucun consultant n'est trouvé, on lève une exception
            throw new \Exception('Aucun consultant à exporter');
        }

        // Convertir les dates pour un affichage lisible dans l'export
        return $consultants->map(function ($consultant) {
            return [
                'id' => $consultant->id,
                'ref_consult' => $consultant->ref_consult,
                'nomcomplet_consult' => $consultant->nomcomplet_consult,
                'email_consul' => $consultant->email_consul,
                'tel_consult' => $consultant->tel_consult,
                'tel1_consult' => $consultant->tel1_consult,
                'type_consult' => $consultant->type_consult,
                'status_consult' => $consultant->status_consult,
                'TelWhatsApp' => $consultant->TelWhatsApp,
                'created_at' => Carbon::parse($consultant->created_at)->format('d/m/Y H:i'),
                'updated_at' => Carbon::parse($consultant->updated_at)->format('d/m/Y H:i'),
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
            'Nom & Prénom',
            'Email',
            'N° Téléphone',
            'N° Téléphone Secondaire',
            'Type Consultant',
            'Statut Consultant',
            'Disponible sur WhatsApp',
            'Date de Création',
            'Date de Modification'
        ];
    }
}
