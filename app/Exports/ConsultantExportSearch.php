<?php

namespace App\Exports;

use App\Models\Consultant;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;
class ConsultantExportSearch implements FromCollection, WithHeadings
{
    // Si tu veux passer un Consultant spécifique, garde cette propriété

    protected $consultants;

    public function __construct(Collection $consultants) // <- c'est la bonne classe
    {
        $this->consultants = $consultants;
    }

    public function collection()
    {
        if ($this->consultants->isEmpty()) {
            throw new \Exception('Aucune donnée à exporter');
        }

        return $this->consultants->map(function ($consultant) {
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
                'Spécialité' => optional($consultant->code_specialite)->nom_specialite ?? 'N/A',
                'Titre' => optional($consultant->code_titre)->nom_titre ?? 'N/A',
                'Service Hopital' => optional($consultant->code_service_hopi)->nom_service_hopi ?? 'N/A',
                'Disponible sur WhatsApp' => $consultant->TelWhatsApp ?? 'N/A',
                'Créé le' => optional($consultant->created_at)->format('d/m/Y H:i:s') ?? 'N/A',
                'Par' => optional($consultant->creator)->email ?? 'N/A',
                'Modifié le' => optional($consultant->updated_at)->format('d/m/Y H:i:s') ?? 'N/A',
                'Par (modif)' => optional($consultant->updater)->email ?? 'N/A',
            ];
        });
    }

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
            'Spécialité',
            'Titre',
            'Service Hopital',
            'Disponible sur WhatsApp',
            'Créé le',
            'Par',
            'Modifié le',
            'Par (modif)',
        ];
    }
}
