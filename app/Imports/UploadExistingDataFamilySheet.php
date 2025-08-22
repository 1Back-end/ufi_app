<?php

namespace App\Imports;

use App\Models\FamilyExam;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UploadExistingDataFamilySheet implements ToArray, withHeadingRow
{
    /**
    * @param array $array
    */
    public function array(array $array): void
    {
        foreach ($array as $datum) {
            FamilyExam::updateOrCreate([
                'code' => $datum['ref_fam'],
                'name' => $datum['nom_fam'],
                'description' => $datum['nom_fam'],
            ]);
        }
    }
}
