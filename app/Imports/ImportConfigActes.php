<?php

namespace App\Imports;

use App\Models\Acte;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ImportConfigActes implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     * @return Acte
     */
    public function model(array $row): Acte
    {
        return new Acte([
            'name' => $row['name'],
            'pu' => $row['pu'],
            'b' => $row['b'],
            'b1' => $row['b1'],
            'delay' => $row['delay'],
            'k_modulateur' => $row['k_modulateur'],
            'type_acte_id' => $row['type_acte_id']
        ]);
    }
}
