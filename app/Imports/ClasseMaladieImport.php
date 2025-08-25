<?php

namespace App\Imports;

use App\Models\ClasseMaladie;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class ClasseMaladieImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        // Ignore l'en-tête
        $rows->shift();
        $auth = auth()->user();

        foreach ($rows as $row) {
            ClasseMaladie::updateOrCreate(
                ['code' => $row[0]],
                [
                    'name'       => $row[1],
                    'created_by' => $auth->id,
                    'updated_by' => $auth->id,
                ]
            );
        }
    }
}
