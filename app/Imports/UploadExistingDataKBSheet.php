<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UploadExistingDataKBSheet implements ToModel, withHeadingRow
{
    /**
     * @param $row
     */
    public function model($row)
    {
        dd($row);
    }
}
