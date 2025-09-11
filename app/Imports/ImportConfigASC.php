<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ImportConfigASC implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Actes' => new ImportConfigActes(),
            'Consultations' => new ImportConfigConsultations(),
            'Soins' => new ImportConfigSoins()
        ];
    }
}
