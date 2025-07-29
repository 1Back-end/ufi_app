<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class UploadExistingDataImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
//            'Examen cleaned' => new UploadExistingDataFirstSheetImport(),
            'KB' => new UploadExistingDataKBSheet(),
        ];
    }
}
