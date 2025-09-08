<?php

namespace App\Imports;

use App\Models\Assureur;
use App\Models\Centre;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AssurancesImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $ref_assur         = $row['code_assur'] == 'NULL' || empty($row['code_assur']) ? null : $row['code_assur'];
            $nom_assur         = $row['nom_assur'] == 'NULL' || empty($row['nom_assur']) ? null : $row['nom_assur'];
            $adresse_assur     = $row['adresse_assur'] == 'NULL' || empty($row['adresse_assur']) ? 'A MODIFIER' : $row['adresse_assur'];
            $Contact_assur     = $row['contact_assur'] == 'NULL' || empty($row['contact_assur']) ? null : $row['contact_assur'];
            $Reg_com_assur     = $row['reg_com_assur'] == 'NULL' || empty($row['reg_com_assur']) ? 'A MODIFIER' : $row['reg_com_assur'];
            $num_contrib_assur = $row['num_contrib_assur'] == 'NULL' || empty($row['num_contrib_assur']) ? 'A MODIFIER' : $row['num_contrib_assur'];
            $bp_assur          = $row['bp_assur'] == 'NULL' || empty($row['bp_assur']) ? 'A MODFIER' : $row['bp_assur'];
            $fax_assur         = $row['fax_assur'] == 'NULL' || empty($row['fax_assur']) ? 'A MODIFIER' : $row['fax_assur'];
            $Email_assur       = $row['email_assur'] == 'NULL' || empty($row['email_assur']) ? 'assurance' . $index . '@example.com' : $row['email_assur'];
            $Tel_assur         = empty($row[13]) || $row[13] == 'NULL' ? '' : $row[13];

            $code_quot = Quotation::find($row['id']);
            $code_centre = Centre::first();

            if (!$code_quot) {
                continue; // Ignore si la quotation n'existe pas
            }

            $assureur = Assureur::where('ref', $row['code_assur'])
                ->first();

            if ($assureur) {
                $assureur->update([
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
                ]);
            }
             else {
                Assureur::create(
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


}
