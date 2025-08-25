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
            'KB' => new UploadExistingDataKBSheet(),
            'family' => new UploadExistingDataFamilySheet(),
            'sub_family' => new UploadExistingDataSubFamillySheet(),
            'paillasse' => new UploadExistingDataPaillasseSheet(),
            'tube' => new UploadExistingDataTubePrelevementSheet(),
            'Examen' => new UploadExistingExamen(),
            'Valeurs normales paillasses' => new UploadExistingElementPaillasse(),
        ];
    }
}
