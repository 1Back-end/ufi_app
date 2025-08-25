<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UploadExistingDataFirstSheetImport implements ToModel, WithHeadingRow
{

    public function model($row)
    {
        dd($row);
    }
}
