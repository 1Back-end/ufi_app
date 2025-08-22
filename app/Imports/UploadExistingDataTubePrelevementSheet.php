<?php

namespace App\Imports;

use App\Models\TubePrelevement;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UploadExistingDataTubePrelevementSheet implements ToArray, WithHeadingRow
{
    /**
    * @param array $array
    */
    public function array(array $array): void
    {
        foreach ($array as $item) {
            TubePrelevement::updateOrCreate([
                'code' => str($item['code_tube'])->slug('')->upper(),
            ], [
                'code' => str($item['code_tube'])->slug( '')->upper(),
                'name' => $item['nom_tube'],
            ]);
        }
    }
}
