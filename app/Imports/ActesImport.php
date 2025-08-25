<?php

namespace App\Imports;

use App\Models\Acte;
use App\Models\Quotation;
use App\Models\TypeActe;
use Faker\Factory as Faker;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ActesImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        ini_set('max_execution_time', 300);
        $authId = auth()->id();
        $faker = Faker::create();

        $rows->shift(); // Supprimer la ligne d’en-tête

        foreach ($rows as $row) {
            $Actes = trim($row[0] ?? '');
            $Prix = trim($row[1] ?? '');
            $Type = trim($row[2] ?? '');
            $B = trim($row[3] ?? '');
            $B1 = trim($row[4] ?? '');
            $taux_quotation = trim($row[5] ?? '');

            if (
                in_array(strtoupper($Actes), ['', 'NULL'], true) ||
                in_array(strtoupper($Type), ['', 'NULL'], true) ||
                !is_numeric($Prix) || !is_numeric($B) || !is_numeric($B1)
            ) {
                continue;
            }

            // ✅ Conversion sécurisée
            $Prix = (float) $Prix;
            $B = (int) $B;
            $B1 = (int) $B1;
            $taux_quotation = is_numeric($taux_quotation) ? (float) $taux_quotation : 0;

            // ✅ Type d'Acte
            $type_actes = TypeActe::firstOrCreate(
                ['name' => $Type],
                [
                    'created_by' => $authId,
                    'updated_by' => $authId,
                    'k_modulateur' => 1,
                    'b' => $B,
                    'b1' => $B1,
                ]
            );

            // ✅ Quotation
            Quotation::firstOrCreate(
                ['code' => 'COT' . now()->format('ymdHis') . mt_rand(10, 99)],
                [
                    'taux' => $taux_quotation,
                    'description' => $faker->sentence
                ]
            );

            Acte::updateOrCreate(
                [
                    'name' => $Actes,
                    'type_acte_id' => $type_actes->id,
                ],
                [
                    'created_by' => $authId,
                    'updated_by' => $authId,
                    'pu' => $Prix,
                    'delay' => 1,
                    'k_modulateur' => 1,
                    'b' => $B,
                    'b1' => $B1,
                ]
            );
        }
    }



}
