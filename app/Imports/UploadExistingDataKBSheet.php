<?php

namespace App\Imports;

use App\Models\KbPrelevement;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UploadExistingDataKBSheet implements ToArray, withHeadingRow
{
    /**
     * @param array $array
     */
    public function array(array $array): void
    {
        $amounts = [
            '1' => 800,
            '1.5' => 1200,
            '3' => 2400
        ];

        foreach ($array as $datum) {
            KbPrelevement::updateOrCreate([
                'code' => 'KB' . $amounts[(string)$datum['value_kb']],
            ], [
                'name' => 'KB' . $amounts[(string)$datum['value_kb']],
                'code' => 'KB' . $amounts[(string)$datum['value_kb']],
                'amount' => $amounts[(string)$datum['value_kb']],
            ]);
        }
//        dd($data);
    }
}
