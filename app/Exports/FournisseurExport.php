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
        // Récupérer uniquement les fournisseurs qui ne sont pas supprimés
        $fournisseurs = Fournisseurs::where('is_deleted', false)->get();

        // Si la collection est vide, lever une exception
        if ($fournisseurs->isEmpty()) {
            throw new \Exception('Aucune donnée à exporter');
        }

        return $fournisseurs->map(function ($fournisseur) {
            return [
                "id" => $fournisseur->id,
                "nom" => $fournisseur->nom,
                "adresse" => $fournisseur->adresse,
                "tel" => $fournisseur->tel,
                "email" => $fournisseur->email,
                "fax" => $fournisseur->fax,
                "ville" => $fournisseur->ville,
                "pays" => $fournisseur->pays,
                "state" => $fournisseur->state,
                "status" => $fournisseur->status,
                'created_at' => Carbon::parse($fournisseur->created_at)->format('d/m/Y H:i'),
                'updated_at' => Carbon::parse($fournisseur->updated_at)->format('d/m/Y H:i'),
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
            'Nom',
            'Adresse',
            'Numéro de téléphone',
            'Email',
            'Fax',
            'Ville',
            'Pays',
            'Etat/Région',
            'Statut',
            'Date de Création',
            'Date de Modification'
        ];
    }
}
