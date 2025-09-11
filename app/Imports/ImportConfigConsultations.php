<?php

namespace App\Imports;

use App\Models\Consultation;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ImportConfigConsultations implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     * @return Consultation
     */
    public function model(array $row): Consultation
    {
        return new Consultation([
            'typeconsultation_id' => $row['typeconsultation_id'],
            'pu' => $row['pu'],
            'pu_default' => $row['pu'],
            'name' => $row['name'],
            'validation_date' => $row['validation_date'],
        ]);
    }
}
