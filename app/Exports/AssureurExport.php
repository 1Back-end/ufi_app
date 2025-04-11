<?php

namespace App\Exports;

use App\Models\Assureur;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;

class AssureurExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $assureurs = Assureur::select(
            'id',
            'ref',
            'nom',
            'nom_abrege',
            'adresse',
            'tel',
            'tel1',
            'reg_com',
            'num_com',
            'code_type',
            'bp',
            'fax',
            'email',
            'BM',
            'ref',
            'status',
            'created_at',
            'updated_at'
        )->where('is_deleted', false)->get();

        if ($assureurs->isEmpty()) {
            throw new \Exception('Aucun assureur à exporter');
        }

        return $assureurs->map(function($assureur) {
            return [
                'id' => $assureur->id,
                'ref' => $assureur->ref,
                'nom' => $assureur->nom,
                'nom_abrege' => $assureur->nom_abrege,
                'adresse' => $assureur->adresse,
                'tel' => $assureur->tel,
                'tel1' => $assureur->tel1,
                'reg_com' => $assureur->reg_com,
                'num_com' => $assureur->num_com,
                'code_type' => $assureur->code_type,
                'bp' => $assureur->bp,
                'fax' => $assureur->fax,
                'email' => $assureur->email,
                'BM' => $assureur->BM,
                'status' => $assureur->status,
                'created_at' => Carbon::parse($assureur->created_at)->format('d/m/Y H:i'),
                'updated_at' => Carbon::parse($assureur->updated_at)->format('d/m/Y H:i'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            '#',
            'Référence',
            'Nom',
            'Nom Abrege',
            'Adresse',
            'Téléphone',
            'Téléphone Secondaire',
            'Registre du commerce',
            'Numéro du contribuable ',
            'Type de Consultant',
            'Boîte Postale',
            'Fax',
            'Email',
            'BM',
            'Référence',
            'Statut',
            'Date de Création',
            'Date de Modification'
        ];
    }

}
