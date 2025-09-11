<?php

namespace App\Imports;

use App\Models\Soins;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ImportConfigSoins implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     * @return Soins
     */
    public function model(array $row): Soins
    {
        return new Soins([
            'type_soin_id' => $row['type_soin_id'],
            'pu' => $row['pu'],
            'pu_default' => $row['pu'],
            'name' => $row['name'],
        ]);
    }
}
