<?php

namespace App\Imports;

use App\Models\Assureur;
use App\Models\Centre;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class AssurancesImport implements ToCollection
{
    public function collection(Collection $rows)
    {

        $rows->shift(); // Supprime l'en-tête

        foreach ($rows as $index => $row) {

            $ref_assur         = trim((string) ($row[1] ?? ''));
            $nom_assur         = trim((string) ($row[2] ?? ''));
            $adresse_assur     = trim((string) ($row[3] ?? ''));
            $Contact_assur     = trim((string) ($row[4] ?? ''));
            $code_quot         = trim((string) ($row[18] ?? ''));
            $Reg_com_assur     = trim((string) ($row[6] ?? ''));
            $num_contrib_assur = trim((string) ($row[7] ?? ''));
            $bp_assur          = trim((string) ($row[8] ?? ''));
            $fax_assur         = trim((string) ($row[9] ?? ''));
            $Email_assur       = trim((string) ($row[12] ?? 'assurance'.$index.'@example.com'));
            $Tel_assur         = trim((string) ($row[13] ?? ''));

            $code_quot = Quotation::find($code_quot);
            $code_centre = Centre::first();

            if (!$code_quot) {
                continue; // Ignore si la quotation n'existe pas
            }

            // Crée ou met à jour l'assureur dans la base
            Assureur::updateOrCreate(
                [
                    'email' => $Email_assur,
                    'tel' => $Contact_assur,
                    'tel1' => $Tel_assur,
                    'ref' => $ref_assur,
                    'Reg_com' => $Reg_com_assur,
                    'num_com' => $num_contrib_assur,
                ],
                [
                    'ref' => $ref_assur,
                    'nom' => $nom_assur,
                    'email' => $Email_assur,
                    'nom_abrege' => $nom_assur,
                    'adresse' => $adresse_assur,
                    'tel' => $Contact_assur,
                    'tel1' => $Tel_assur,
                    'code_quotation' => $code_quot->id,
                    'code_centre' => $code_centre->id,
                    'Reg_com' => $Reg_com_assur,
                    'num_com' => $num_contrib_assur,
                    'bp' => $bp_assur,
                    'fax' => $fax_assur,
                    'code_type' => 'Principale',
                    'created_by' => User::first()->id,
                    'updated_by' => User::first()->id,
                ]
            );
        }
    }


}
