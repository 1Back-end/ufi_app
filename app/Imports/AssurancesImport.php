<?php

namespace App\Imports;

use App\Models\Assureur;
use App\Models\Centre;
use App\Models\Consultant;
use App\Models\Quotation;
use Faker\Factory as Faker;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
class AssurancesImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        ini_set('max_execution_time', 300);

        $authId = auth()->id();
        $faker = Faker::create();

        $rows->shift(); // Supprime l'en-tête

        // Tableau pour garder la trace des valeurs uniques déjà rencontrées dans le fichier
        $emails = [];
        $tels = [];
        $tels1 = [];
        $refs = [];
        $reg_coms = [];
        $num_coms = [];

        foreach ($rows as $index => $row) {

            $ref_assur         = trim((string) ($row[1] ?? ''));
            $nom_assur         = trim((string) ($row[2] ?? ''));
            $adresse_assur     = trim((string) ($row[3] ?? ''));
            $Contact_assur     = trim((string) ($row[4] ?? ''));
            $code_quot         = trim((string) ($row[5] ?? ''));
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

            // Vérifie les doublons dans le fichier
            if (
                in_array($Email_assur, $emails) ||
                in_array($Contact_assur, $tels) ||
                in_array($Tel_assur, $tels1) ||
                in_array($ref_assur, $refs) ||
                in_array($Reg_com_assur, $reg_coms) ||
                in_array($num_contrib_assur, $num_coms)
            ) {
                continue; // Ignore cette ligne si doublon dans le fichier
            }

            // Ajoute les valeurs dans les tableaux de suivi pour éviter les doublons
            $emails[] = $Email_assur;
            $tels[] = $Contact_assur;
            $tels1[] = $Tel_assur;
            $refs[] = $ref_assur;
            $reg_coms[] = $Reg_com_assur;
            $num_coms[] = $num_contrib_assur;

            // Crée ou met à jour l'assureur dans la base
            Assureur::updateOrCreate(
                ['email' => $Email_assur], // clé pour update
                [
                    'ref' => $ref_assur,
                    'nom' => $nom_assur,
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
                ]
            );
        }
    }


}
