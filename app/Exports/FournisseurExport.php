<?php

namespace App\Exports;

use App\Models\Fournisseurs;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FournisseurExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $fournisseurs = Fournisseurs::with(["creator", "updater"])
            ->orderBy('full_name', 'asc')
            ->get();

        if ($fournisseurs->isEmpty()) {
            throw new \Exception('Aucune donnée à exporter');
        }

        return $fournisseurs->map(function ($fournisseur, $index) {
            return [
                "id" => $index + 1,
                "full_name" => $fournisseur->full_name,
                "company_name" => $fournisseur->company_name,
                "address" => $fournisseur->address,
                "phone_number" => $fournisseur->phone_number,
                "second_phone_number" => $fournisseur->second_phone_number,
                "email" => $fournisseur->email,
                "tax_number" => $fournisseur->tax_number,
                "business_registration_number" => $fournisseur->business_registration_number,
                "website" => $fournisseur->website,
                "city" => $fournisseur->city,
                "country" => $fournisseur->country,
                "contact_person" => $fournisseur->contact_person,
                "contact_person_phone" => $fournisseur->contact_person_phone,
                "is_active" => $fournisseur->is_active ? 'Actif' : 'Inactif',
                'created_at' => $fournisseur->created_at ? Carbon::parse($fournisseur->created_at)->format('d/m/Y H:i') : '',
                'updated_at' => $fournisseur->updated_at ? Carbon::parse($fournisseur->updated_at)->format('d/m/Y H:i') : '',
            ];
        });
    }

    /**
     * Les en-têtes du fichier Excel
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            '#',
            'Nom complet',
            'Entreprise',
            'Adresse',
            'Téléphone principal',
            'Second téléphone',
            'Email',
            'Numéro de contribuable',
            'N° RCCM / Enreg.',
            'Site web',
            'Ville',
            'Pays',
            'Personne contact',
            'Tél. contact',
            'Statut',
            'Date de Création',
            'Date de Modification'
        ];
    }
}
