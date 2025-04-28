<?php

namespace App\Exports;

use App\Models\Fournisseurs;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FournisseurSearchExport implements FromCollection,WithHeadings
{
    protected $fournisseurs;

    public function __construct(Collection $fournisseurs)
    {
        $this->fournisseurs = $fournisseurs;
    }

    public function collection()
    {
        return $this->fournisseurs->map(function ($fournisseur) {
            return [
                "id" => $fournisseur->id,
                "nom" => $fournisseur->nom,
                "adresse" => $fournisseur->adresse,
                "tel" => $fournisseur->tel,
                "email" => $fournisseur->email,
                "fax" => $fournisseur->fax,
                "pays" => $fournisseur->pays,
                "ville" => $fournisseur->ville,
                "etat/region" => $fournisseur->state,
                "créé le" => Carbon::parse($fournisseur->created_at)->format('d/m/Y H:i'),
                "modifié le" => Carbon::parse($fournisseur->updated_at)->format('d/m/Y H:i'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            '#',
            'Nom',
            'Adresse',
            'Numéro de téléphone',
            'Email',
            'Fax',
            'Pays',
            'Ville',
            'État/Région',
            'Date de Création',
            'Date de Modification'
        ];
    }
}
