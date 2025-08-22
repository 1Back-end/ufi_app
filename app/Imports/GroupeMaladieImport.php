<?php
namespace App\Imports;

use App\Models\GroupeMaladie;
use App\Models\ClasseMaladie;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class GroupeMaladieImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        // Supposons que la première ligne contient les entêtes
        $rows->shift();
        $auth = auth()->user();

        foreach ($rows as $row) {
            $codeGroupe = trim($row[0]);
            $codeClasse = trim($row[1]);
            $nomGroupe  = trim($row[2]);

            $classe = ClasseMaladie::where('code', $codeClasse)->first();

            if ($classe && !GroupeMaladie::where('code', $codeGroupe)->exists()) {
                GroupeMaladie::updateOrCreate([
                    'classe_maladie_id' => $classe->id,
                    'name' => $nomGroupe,
                    'code' => $codeGroupe,
                    'created_by' => $auth->id,
                    'updated_by' => $auth->id,
                ]);
            }
        }
    }
}

