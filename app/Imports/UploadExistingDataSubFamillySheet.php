<?php

namespace App\Imports;

use App\Models\FamilyExam;
use App\Models\SubFamilyExam;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UploadExistingDataSubFamillySheet implements ToArray, withHeadingRow
{
    /**
     * @param array $array
     */
    public function array(array $array)
    {
        foreach ($array as $item) {
            $familly = FamilyExam::where('code', $item['code_fam'])->first();
            SubFamilyExam::updateOrCreate([
                'code' => $item['ref_sous_famille'],
            ], [
                'code' => $item['ref_sous_famille'],
                'name' => $item['nom_sous_famille'],
                'description' => $item['nom_sous_famille'],
                'family_exam_id' => $familly->id,
            ]);
        }
    }
}
