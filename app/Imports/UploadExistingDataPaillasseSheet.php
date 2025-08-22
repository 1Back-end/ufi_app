<?php

namespace App\Imports;

use App\Models\Paillasse;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UploadExistingDataPaillasseSheet implements ToArray, WithHeadingRow
{
    /**
    * @param array $array
    */
    public function array(array $array)
    {
//        dd($array);

        foreach ($array as $item) {
            Paillasse::updateOrCreate([
                'code' => str($item['ref_paillasse'])->slug()->upper(),
            ], [
                'code' => str($item['ref_paillasse'])->slug()->upper(),
                'name' => $item['nom_paillasse'],
                'description' => $item['nom_paillasse'],
            ]);
        }
    }
}
